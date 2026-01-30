<?php

declare(strict_types=1);

namespace APITester\Test;

use APITester\Authenticator\Authenticator;
use APITester\Authenticator\Exception\AuthenticationException;
use APITester\Authenticator\Exception\AuthenticationLoadingException;
use APITester\Config;
use APITester\Definition\Api;
use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Tokens;
use APITester\Definition\Loader\DefinitionLoader;
use APITester\Definition\Loader\Exception\DefinitionLoaderNotFoundException;
use APITester\Definition\Loader\Exception\DefinitionLoadingException;
use APITester\Definition\Operation;
use APITester\Preparator\Exception\InvalidPreparatorConfigException;
use APITester\Preparator\Exception\PreparatorLoadingException;
use APITester\Preparator\TestCasesPreparator;
use APITester\Requester\Exception\RequesterNotFoundException;
use APITester\Requester\Requester;
use APITester\Test\Exception\SuiteNotFoundException;
use APITester\Util\Object_;
use APITester\Util\TestCase\Printer\DefaultPrinter;
use APITester\Util\TestCase\Printer\TestDoxPrinter;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\ResultCache\NullResultCache;
use PHPUnit\TextUI\CliArguments\Builder;
use PHPUnit\TextUI\CliArguments\Configuration;
use PHPUnit\TextUI\CliArguments\Mapper;
use PHPUnit\TextUI\Configuration\Merger;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\TextUI\XmlConfiguration\DefaultConfiguration;
use PHPUnit\TextUI\XmlConfiguration\Loader;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

final class Plan
{
    private const NON_PHPUNIT_OPTIONS = [
        'config',
        'quiet',
        'ansi',
        'no-ansi',
        'no-interaction',
        'suite',
        'set-baseline',
        'update-baseline',
        'ignore-baseline',
        'only-baseline',
        'part',
        'operation-id',
    ];

    private readonly Authenticator $authenticator;

    /**
     * @var DefinitionLoader[]
     */
    private readonly array $definitionLoaders;

    /**
     * @var TestCasesPreparator[]
     */
    private readonly array $preparators;

    /**
     * @var class-string<Requester>[]
     */
    private readonly array $requesters;

    /**
     * @var array<string, TestResult>
     */
    private array $results = [];

    private readonly TestRunner $runner;

    /**
     * @param TestCasesPreparator[]     $preparators
     * @param class-string<Requester>[] $requesters
     * @param DefinitionLoader[]        $definitionLoaders
     */
    public function __construct(
        ?array $preparators = null,
        ?array $requesters = null,
        ?array $definitionLoaders = null,
        Authenticator $authenticator = null,
        private LoggerInterface $logger = new NullLogger()
    ) {
        if (!\defined('PROJECT_DIR')) {
            \define('PROJECT_DIR', \dirname(__DIR__, 2));
        }
        $this->preparators = $preparators ?? Object_::getImplementations(TestCasesPreparator::class);
        $this->requesters = $requesters ?? Object_::getImplementationsClasses(Requester::class);
        $this->definitionLoaders = $definitionLoaders ?? Object_::getImplementations(DefinitionLoader::class);
        $this->authenticator = $authenticator ?? new Authenticator();
        $this->runner = new TestRunner();
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     * @throws RequesterNotFoundException
     * @throws InvalidPreparatorConfigException
     * @throws SuiteNotFoundException
     */
    public function execute(
        Config\Plan $testPlanConfig,
        string $suiteName = '',
        array $options = []
    ): bool {
        $bootstrap = $testPlanConfig->getBootstrap();
        if ($bootstrap !== null) {
            require_once $bootstrap;
        }
        $suites = $testPlanConfig->getSuites();
        $suites = $this->selectSuite($suiteName, $suites);
        foreach ($suites as $suiteConfig) {
            if (!empty($options['set-baseline'])) {
                $this->resetBaseLine($suiteConfig);
            }
            $testSuite = $this->prepareSuite($suiteConfig, $options);

            $this->runSuite($suiteConfig, $testSuite, $options);
            if (!empty($options['update-baseline']) || !empty($options['set-baseline'])) {
                $this->updateBaseLine($suiteConfig);
            }
            break;
        }

        return $this->isSuccessful();
    }

    /**
     * @return array<string, TestResult>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param array<Config\Suite> $suites
     *
     * @throws SuiteNotFoundException
     *
     * @return iterable<Config\Suite>
     */
    private function selectSuite(string $suiteName, array $suites): iterable
    {
        if ($suiteName !== '') {
            $indexSuites = collect($suites)
                ->keyBy('name')
            ;
            if ($indexSuites->has($suiteName)) {
                $suites = $indexSuites->where('name', $suiteName);
            } else {
                throw new SuiteNotFoundException();
            }
        }

        return $suites;
    }

    private function resetBaseLine(Config\Suite $suiteConfig): void
    {
        $baselineFile = $suiteConfig
            ->getFilters()
            ->getBaseline()
        ;
        if (file_exists($suiteConfig->getFilters()->getBaseline())) {
            unlink($baselineFile);
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     * @throws InvalidPreparatorConfigException
     * @throws RequesterNotFoundException
     *
     * @return Suite<\PHPUnit\Framework\TestCase, HttpKernelInterface>
     */
    private function prepareSuite(Config\Suite $suiteConfig, array $options = []): TestSuite
    {
        $testCaseClass = Object_::validateClass(
            $suiteConfig->getTestCaseClass(),
            \PHPUnit\Framework\TestCase::class
        );
        $kernel = $this->loadSymfonyKernel($suiteConfig, $testCaseClass);
        $definition = $this->loadApiDefinition($suiteConfig, $options);
        $requester = $this->loadRequester(
            $suiteConfig->getRequester(),
            $suiteConfig->getBaseUrl() ?? $definition->getUrl(),
            $kernel
        );
        $tokens = $this->authenticate($suiteConfig, $definition, $requester);
        $preparators = $this->loadPreparators($suiteConfig->getPreparators(), $tokens);

        $suite = TestSuite::empty($suiteConfig->getName());
        foreach ($this->prepareTestCases($definition, $preparators, $options, $suiteConfig, $requester, $testCaseClass) as $test) {
            $suite->addTest($test);
        }

        return $suite;
    }

    private function prepareTestCases(Api $definition, array $preparators, array $options, Config\Suite $config, Requester $requester, string $testCaseClass): Collection
    {
        /** @var Collection<int, TestCase> $allTests */
        $allTests = collect();
        foreach ($preparators as $preparator) {
            $preparator->setLogger($this->logger);
            $preparator->setSchemaValidationBaseline($config->getFilters()->getSchemaValidationBaseline());
            $operations = $definition->getOperations();
            try {
                $operations = $this->filterOperation($operations, $config);
                $tests = $preparator->doPrepare($operations);

                if (!empty($options['ignore-baseline'])) {
                    $tests = $this->filterTestCases($tests, $config);
                }
                if (!empty($options['only-baseline'])) {
                    $tests = $this->filterOnlyTestCases($tests, $config);
                }

                foreach ($tests as $testCase) {
                    $testCase->setRequester($requester);
                    $testCase->setLogger($this->logger);
                    $testCase->setBeforeCallbacks($config->getBeforeTestCaseCallbacks());
                    $testCase->setAfterCallbacks($config->getAfterTestCaseCallbacks());
                    $testCase->setSpecification($definition->getSpecification());
                    $allTests->add($testCase);
                }
            } catch (PreparatorLoadingException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $allTests
            ->sortBy('operation')
            ->values()
            ->filter(fn (TestCase $testCase, int $index) => $this->indexInPart(
                $this->part,
                $index,
                $allTests->count(),
            ))
            ->map(
                fn (TestCase $testCase) => $testCase->toPhpUnitTestCase($testCaseClass)
            )
        ;
    }

    private function filterOperation(Operations $operations, Config\Suite $config): Operations
    {
        return $operations->filter(fn (Operation $operation) => $config->getFilters()->includes($operation));
    }

    /**
     * @param iterable<array-key, TestCase> $tests
     *
     * @return iterable<array-key, TestCase>
     */
    private function filterTestCases(iterable $tests, Config\Suite $config): iterable
    {
        $excludedTests = array_column(
            $this->toTestCaseFilter($config->getFilters()->getBaseLineExclude()),
            'name'
        );

        return collect($tests)->filter(static fn (TestCase $test) => !\in_array(
            $test->getName(),
            $excludedTests,
            true
        ));
    }

    /**
     * @param iterable<array-key, TestCase> $tests
     *
     * @return iterable<array-key, TestCase>
     */
    private function filterOnlyTestCases(iterable $tests, Config\Suite $config): iterable
    {
        $includedTests = array_column(
            $this->toTestCaseFilter($config->getFilters()->getBaseLineExclude()),
            'name'
        );

        return collect($tests)->filter(static fn (TestCase $test) => \in_array(
            $test->getName(),
            $includedTests,
            true
        ));
    }


    private function indexInPart(?string $part, int $index, int $total): bool
    {
        if ($part === null) {
            return true;
        }

        [$partIndex, $partsCount] = explode('/', $part);

        $partIndex = (int) $partIndex;
        $partsCount = (int) $partsCount;

        if ($partsCount > 0 && $index <= $total) {
            $span = (int) ceil($total / $partsCount);
            $from = $span * ($partIndex - 1);
            $to = $span * $partIndex;

            return $from <= $index && $index < $to;
        }

        return false;
    }

    /**
     * @param array<array<string, string>> $filter
     *
     * @return array<iterable<string, string>>
     */
    private function toTestCaseFilter(array $filter): array
    {
        /** @var array<iterable<string, string>> */
        return collect($filter)
            ->map(
                static fn ($value) => collect($value)
                    ->filter(static fn ($value, $key) => str_starts_with(
                        $key,
                        'testcase.'
                    ))
                    ->mapWithKeys(
                        static fn ($value, $key) => [
                            str_replace('testcase.', '', $key) => $value,
                        ]
                    )
            )
            ->filter()
            ->toArray()
        ;
    }

    /**
     * @param Suite<\PHPUnit\Framework\TestCase, HttpKernelInterface> $testSuite
     * @param array<string, mixed>                                    $options
     */
    private function runSuite(Config\Suite $suiteConfig, TestSuite $testSuite, array $options): void
    {
        $this->runner->run(
            (new Merger())->merge($this->getPhpUnitArguments($options), DefaultConfiguration::create()),
            new NullResultCache(),
            $testSuite,
        );
        restore_exception_handler();
    }

    private function updateBaseLine(Config\Suite $suiteConfig): void
    {
        $exclude = [];
        foreach ($this->results as $result) {
            foreach (array_merge($result->failures(), $result->errors()) as $failure) {
                /** @var TestCase|null $testCase */
                $testCase = $failure->failedTest();
                if ($testCase === null) {
                    continue;
                }
                $exclude[] = [
                    'testcase.name' => $testCase->getName(),
                ];
            }
        }
        $suiteConfig
            ->getFilters()
            ->writeBaseline($exclude)
        ;
    }

    private function isSuccessful(): bool
    {
        foreach ($this->results as $suiteResult) {
            if ($suiteResult->failureCount() > 0 || $suiteResult->errorCount() > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param class-string<\PHPUnit\Framework\TestCase> $testCaseClass
     */
    private function loadSymfonyKernel(Config\Suite $suiteConfig, string $testCaseClass): ?Kernel
    {
        $kernel = null;
        if ($suiteConfig->getSymfonyKernelClass() !== null) {
            $kernelClass = Object_::validateClass(
                $suiteConfig->getSymfonyKernelClass(),
                HttpKernelInterface::class
            );
            if (method_exists($testCaseClass, 'getKernel')) {
                $kernel = $this->getTestCaseKernel($testCaseClass, $kernelClass);
            } else {
                $kernel = $this->bootSymfonyKernel($kernelClass);
            }
        }

        return $kernel;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     */
    private function loadApiDefinition(Config\Suite $config, array $options = []): Api
    {
        $definitionLoader = $this->getConfiguredLoader(
            $config->getDefinition()
                ->getFormat()
        );

        $filters = [];
        if (isset($options['operation-id'])) {
            $filters['operationId'] = array_map(
                'trim',
                explode(',', (string) $options['operation-id'])
            );
        }

        return $definitionLoader->load(
            $config->getDefinition()
                ->getPath(),
            filters: $filters,
        );
    }

    /**
     * @throws RequesterNotFoundException
     */
    private function loadRequester(string $name, string $baseUri, ?HttpKernelInterface $kernel = null): Requester
    {
        foreach ($this->requesters as $requester) {
            if ($requester::getName() === $name) {
                $object = new $requester($baseUri);
                if ($kernel !== null && method_exists($object, 'setKernel')) {
                    $object->setKernel($kernel);
                }

                return $object;
            }
        }

        throw new RequesterNotFoundException($name);
    }

    private function authenticate(Config\Suite $config, Api $api, Requester $requester): Tokens
    {
        $tokens = new Tokens();
        foreach ($config->getAuthentications() as $authConf) {
            try {
                $tokens->add($this->authenticator->authenticate($authConf, $api, $requester));
            } catch (AuthenticationException|AuthenticationLoadingException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $tokens;
    }

    /**
     * @param array<string, array<string, mixed>> $preparators
     *
     * @throws InvalidPreparatorConfigException
     *
     * @return TestCasesPreparator[]
     */
    private function loadPreparators(array $preparators, Tokens $tokens): array
    {
        if (\count($preparators) === 0) {
            return $this->preparators;
        }
        $configuredPreparators = [];
        foreach ($preparators as $name => $preparatorConfig) {
            $preparator = collect($this->preparators)
                ->where('name', $name)
                ->first()
            ;
            if ($preparator === null) {
                throw new InvalidPreparatorConfigException("Preparator {$name} not found.");
            }
            $preparator->configure($preparatorConfig);
            $preparator->setTokens($tokens);
            $configuredPreparators[] = $preparator;
        }

        return $configuredPreparators;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return string[]
     */
    private function getPhpUnitArguments(array $options): Configuration
    {
        $options = $this->getPhpUnitOptions($options);
        $arguments = (new Builder())->fromParameters($options, []);

        return $arguments;
    }

    /**
     * @param class-string $testCaseClass
     * @param class-string $kernelClass
     */
    private function getTestCaseKernel(string $testCaseClass, string $kernelClass): Kernel
    {
        $className = 'TestCaseKernelProvider';
        if (!class_exists('TestCaseKernelProvider')) {
            $code = <<<CODE_SAMPLE
                class {$className} extends {$testCaseClass} {
                    public function __construct() {
                        parent::__construct('test');
                        self::\$kernelClass = '{$kernelClass}';
                        if (method_exists(\$this, 'resetDatabase'))
                            \$this->resetDatabase();
                        if (method_exists(\$this, 'bootKernel'))
                            \$this->bootKernel();
                    }
                    public function getTestCaseKernel() {
                        return \$this->getKernel();
                    }
                }
                CODE_SAMPLE;
            eval($code);
        }
        $className = '\\' . $className;
        $kernelProvider = new $className();

        return $kernelProvider->getTestCaseKernel();
    }

    /**
     * @param class-string<HttpKernelInterface> $symfonyKernelClass
     */
    private function bootSymfonyKernel(string $symfonyKernelClass): Kernel
    {
        /** @var Kernel $kernel */
        $kernel = new $symfonyKernelClass('test', true);
        $kernel->boot();

        return $kernel;
    }

    /**
     * @throws DefinitionLoaderNotFoundException
     */
    private function getConfiguredLoader(string $format): DefinitionLoader
    {
        foreach ($this->definitionLoaders as $loader) {
            if ($loader::getFormat() === $format) {
                $loader->setLogger($this->logger);

                return $loader;
            }
        }

        throw new DefinitionLoaderNotFoundException($format);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<array-key, string>
     */
    private function getPhpUnitOptions(array $options): array
    {
        $options['colors'] = 'always';
        if (!isset($options['verbose']) || $options['verbose'] === false) {
            //$options['printer'] = ($options['testdox'] ?? false) === true ? TestDoxPrinter::class : DefaultPrinter::class;
        }
        $options = array_filter(
            $options,
            static fn ($key) => !\in_array($key, self::NON_PHPUNIT_OPTIONS, true),
            ARRAY_FILTER_USE_KEY
        );

        return array_filter(
            array_map(
                static function (string $key, $value) {
                    if ($value === null) {
                        return null;
                    }
                    if ($value === true) {
                        return "--{$key}";
                    }

                    if (!\is_scalar($value)) {
                        throw new \InvalidArgumentException('Options must be scalar');
                    }

                    return $value !== false ? "--{$key}={$value}" : null;
                },
                array_keys($options),
                array_values($options),
            )
        );
    }
}
