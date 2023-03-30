<?php

declare(strict_types=1);

namespace APITester\Test;

use APITester\Authenticator\Authenticator;
use APITester\Authenticator\Exception\AuthenticationException;
use APITester\Authenticator\Exception\AuthenticationLoadingException;
use APITester\Config;
use APITester\Definition\Api;
use APITester\Definition\Collection\Tokens;
use APITester\Definition\Loader\DefinitionLoader;
use APITester\Definition\Loader\Exception\DefinitionLoaderNotFoundException;
use APITester\Definition\Loader\Exception\DefinitionLoadingException;
use APITester\Preparator\Exception\InvalidPreparatorConfigException;
use APITester\Preparator\TestCasesPreparator;
use APITester\Requester\Exception\RequesterNotFoundException;
use APITester\Requester\Requester;
use APITester\Test\Exception\SuiteNotFoundException;
use APITester\Util\Object_;
use APITester\Util\TestCase\Printer\DefaultPrinter;
use APITester\Util\TestCase\Printer\TestDoxPrinter;
use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\CliArguments\Builder;
use PHPUnit\TextUI\CliArguments\Mapper;
use PHPUnit\TextUI\TestRunner;
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
        'part',
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
     * @throws AuthenticationLoadingException
     * @throws AuthenticationException
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
            $testSuite = $this->prepareSuite($suiteConfig);
            if (!empty($options['ignore-baseline'])) {
                $testSuite->setIgnoreBaseLine(true);
            }
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
     * @throws AuthenticationException
     * @throws AuthenticationLoadingException
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     * @throws InvalidPreparatorConfigException
     * @throws RequesterNotFoundException
     *
     * @return Suite<\PHPUnit\Framework\TestCase, HttpKernelInterface>
     */
    private function prepareSuite(Config\Suite $suiteConfig): Suite
    {
        $testCaseClass = Object_::validateClass(
            $suiteConfig->getTestCaseClass(),
            \PHPUnit\Framework\TestCase::class
        );
        $kernel = $this->loadSymfonyKernel($suiteConfig, $testCaseClass);
        $definition = $this->loadApiDefinition($suiteConfig);
        $requester = $this->loadRequester(
            $suiteConfig->getRequester(),
            $definition->getUrl(),
            $kernel
        );
        $tokens = $this->authenticate($suiteConfig, $definition, $requester);
        $preparators = $this->loadPreparators($suiteConfig->getPreparators(), $tokens);
        $testSuite = new Suite(
            $suiteConfig->getName(),
            $definition,
            $preparators,
            $requester,
            $suiteConfig->getFilters(),
            $this->logger,
            $testCaseClass,
        );
        $testSuite->setBeforeTestCaseCallbacks($suiteConfig->getBeforeTestCaseCallbacks());
        $testSuite->setAfterTestCaseCallbacks($suiteConfig->getAfterTestCaseCallbacks());

        return $testSuite;
    }

    /**
     * @param Suite<\PHPUnit\Framework\TestCase, HttpKernelInterface> $testSuite
     * @param array<string, mixed>                                    $options
     */
    private function runSuite(Config\Suite $suiteConfig, Suite $testSuite, array $options): void
    {
        $part = $options['part'] ?? null;
        $testSuite->setPart($part !== null ? (string) $part : null);
        $this->results[$suiteConfig->getName()] = $this->runner->run(
            $testSuite,
            $this->getPhpUnitArguments($options, $suiteConfig),
            [],
            false
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
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     */
    private function loadApiDefinition(Config\Suite $config): Api
    {
        $definitionLoader = $this->getConfiguredLoader(
            $config->getDefinition()
                ->getFormat()
        );

        return $definitionLoader->load(
            $config->getDefinition()
                ->getPath()
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

    /**
     * @throws AuthenticationLoadingException
     * @throws AuthenticationException
     */
    private function authenticate(Config\Suite $config, Api $api, Requester $requester): Tokens
    {
        $tokens = new Tokens();
        foreach ($config->getAuthentications() as $authConf) {
            $tokens->add($this->authenticator->authenticate($authConf, $api, $requester));
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
    private function getPhpUnitArguments(array $options, Config\Suite $suiteConfig): array
    {
        $options = $this->getPhpUnitOptions($options);
        $arguments = (new Builder())->fromParameters($options, []);
        $arguments = (new Mapper())->mapToLegacyArray($arguments);

        $phpunitConfig = $suiteConfig->getPhpunitConfig();
        if ($phpunitConfig !== null) {
            $arguments['configurationObject'] = (new Loader())->load($phpunitConfig);
        }

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
            $options['printer'] = ($options['testdox'] ?? false) === true ? TestDoxPrinter::class : DefaultPrinter::class;
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
