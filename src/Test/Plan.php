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
use APITester\Util\Assert;
use APITester\Util\Object_;
use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\CliArguments\Builder;
use PHPUnit\TextUI\CliArguments\Mapper;
use PHPUnit\TextUI\TestRunner;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

final class Plan
{
    private Authenticator $authenticator;

    /**
     * @var DefinitionLoader[]
     */
    private array $definitionLoaders;

    private LoggerInterface $logger;

    /**
     * @var TestCasesPreparator[]
     */
    private array $preparators;

    /**
     * @var class-string<Requester>[]
     */
    private array $requesters;

    /**
     * @var array<string, TestResult>
     */
    private array $results = [];

    private TestRunner $runner;

    /**
     * @param TestCasesPreparator[] $preparators
     * @param class-string<Requester>[] $requesters
     * @param DefinitionLoader[] $definitionLoaders
     */
    public function __construct(
        ?array $preparators = null,
        ?array $requesters = null,
        ?array $definitionLoaders = null,
        Authenticator $authenticator = null,
        ?LoggerInterface $logger = null
    ) {
        if (!\defined('PROJECT_DIR')) {
            \define('PROJECT_DIR', \dirname(__DIR__, 2));
        }
        $this->preparators = $preparators ?? Object_::getImplementations(TestCasesPreparator::class);
        $this->requesters = $requesters ?? Object_::getImplementationsClasses(Requester::class);
        $this->definitionLoaders = $definitionLoaders ?? Object_::getImplementations(DefinitionLoader::class);
        $this->authenticator = $authenticator ?? new Authenticator();
        $this->logger = $logger ?? new NullLogger();
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
        ?string $suiteName = null,
        array $options = []
    ): void {
        $suites = $testPlanConfig->getSuites();
        $suites = $this->selectSuite($suiteName, $suites);
        foreach ($suites as $suiteConfig) {
            $testSuite = $this->prepareTestSuite($suiteConfig);
            $this->results[$suiteConfig->getName()] = $this->runner->run(
                $testSuite,
                (new Mapper())->mapToLegacyArray(
                    (new Builder())->fromParameters(
                        $this->getPhpUnitOptions($options),
                        []
                    )
                ),
                [],
                false
            );
            foreach ($this->results as $result) {
                Assert::same(0, $result->errorCount(), "{$result->errorCount()} Error(s).");
                Assert::same(0, $result->failureCount(), "{$result->failureCount()} Failure(s).");
            }
        }
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
    private function selectSuite(?string $suiteName, array $suites): iterable
    {
        if (null !== $suiteName) {
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
    private function prepareTestSuite(Config\Suite $suiteConfig): Suite
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
     * @param array<string, mixed> $options
     *
     * @return array<array-key, string>
     */
    private function getPhpUnitOptions(array $options): array
    {
        $options['colors'] = 'always';
        $options = array_filter(
            $options,
            static fn ($key) => !\in_array($key, [
                'config',
                'quiet',
                'ansi',
                'no-ansi',
                'no-interaction',
                'suite',
            ], true),
            ARRAY_FILTER_USE_KEY
        );

        return array_filter(
            array_map(
                static function (string $key, $value) {
                    if (null === $value) {
                        return null;
                    }
                    if (true === $value) {
                        return "--{$key}";
                    }

                    /** @var string|bool|int $value */
                    return false !== $value ? "--{$key}={$value}" : null;
                },
                array_keys($options),
                array_values($options),
            )
        );
    }

    /**
     * @param class-string<\PHPUnit\Framework\TestCase> $testCaseClass
     */
    private function loadSymfonyKernel(Config\Suite $suiteConfig, string $testCaseClass): Kernel
    {
        $kernel = null;
        if (null !== $suiteConfig->getSymfonyKernelClass()) {
            $kernelClass = Object_::validateClass(
                $suiteConfig->getSymfonyKernelClass(),
                HttpKernelInterface::class
            );
            if (method_exists($testCaseClass, 'getKernel')) {
                $className = 'TestCaseKernelProvider';
                $code = <<<CODE_SAMPLE
                class {$className} extends {$testCaseClass} {
                    public function __construct() {
                        parent::__construct('test');
                        self::\$kernelClass = '{$kernelClass}';
                        \$this->bootKernel();
                    }
                    public function getTestCaseKernel() {
                        return \$this->getKernel();
                    }
                }
                CODE_SAMPLE;
                eval($code);
                $className = '\\' . $className;
                $kernelProvider = new $className();
                $kernel = $kernelProvider->getTestCaseKernel();
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
                if (null !== $kernel && method_exists($object, 'setKernel')) {
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
        foreach ($config->getAuthentifications() as $authConf) {
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
        if (0 === \count($preparators)) {
            return $this->preparators;
        }
        $configuredPreparators = [];
        foreach ($preparators as $name => $preparatorConfig) {
            $preparator = collect($this->preparators)
                ->where('name', $name)
                ->first()
            ;
            if (null === $preparator) {
                throw new InvalidPreparatorConfigException("Preparator {$name} not found.");
            }
            $preparator->configure($preparatorConfig);
            $preparator->setTokens($tokens);
            $configuredPreparators[] = $preparator;
        }

        return $configuredPreparators;
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
}
