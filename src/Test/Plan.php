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
use APITester\Requester\SymfonyKernelRequester;
use APITester\Test\Exception\SuiteNotFoundException;
use APITester\Util\Object_;
use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\CliArguments\Builder;
use PHPUnit\TextUI\CliArguments\Mapper;
use PHPUnit\TextUI\TestRunner;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

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
     * @var Requester[]
     */
    private array $requesters;

    /**
     * @var array<string, TestResult>
     */
    private array $results = [];

    private TestRunner $runner;

    /**
     * @param TestCasesPreparator[] $preparators
     * @param Requester[]           $requesters
     * @param DefinitionLoader[]    $definitionLoaders
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
        $this->requesters = $requesters ?? Object_::getImplementations(Requester::class);
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
            $this->handleSymfonyKernel($suiteConfig);
            $requester = $this->getRequester($suiteConfig->getRequester());
            $api = $this->getApi($suiteConfig, $requester);
            $tokens = $this->Authenticate($suiteConfig, $api, $requester);
            $preparators = $this->getConfiguredPreparators($suiteConfig->getPreparators(), $tokens);
            $testSuite = new Suite(
                $suiteConfig->getName(),
                $api,
                $preparators,
                $requester,
                $suiteConfig->getFilters(),
                $this->logger,
                Object_::validateClass($suiteConfig->getTestCaseClass(), \PHPUnit\Framework\TestCase::class),
                null !== $suiteConfig->getSymfonyKernelClass() ? Object_::validateClass(
                    $suiteConfig->getSymfonyKernelClass(),
                    KernelInterface::class
                ) : null,
            );
            $testSuite->setBeforeTestCaseCallbacks($suiteConfig->getBeforeTestCaseCallbacks());
            $testSuite->setAfterTestCaseCallbacks($suiteConfig->getAfterTestCaseCallbacks());
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
        }
    }

    public function addRequester(Requester $requester): self
    {
        $this->requesters[] = $requester;

        return $this;
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

    private function handleSymfonyKernel(Config\Suite $suiteConfig): void
    {
        $symfonyKernelClass = $suiteConfig->getSymfonyKernelClass();
        if (null !== $symfonyKernelClass) {
            /** @var Kernel $kernel */
            $kernel = new $symfonyKernelClass('test', true);
            $kernel->boot();
            $this->addRequester(new SymfonyKernelRequester($kernel));
        }
    }

    /**
     * @throws RequesterNotFoundException
     */
    private function getRequester(string $name): Requester
    {
        foreach ($this->requesters as $requester) {
            if ($requester::getName() === $name) {
                return $requester;
            }
        }

        throw new RequesterNotFoundException($name);
    }

    /**
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     */
    private function getApi(Config\Suite $config, Requester $requester): Api
    {
        $definitionLoader = $this->getConfiguredLoader(
            $config->getDefinition()
                ->getFormat()
        );
        $api = $definitionLoader->load(
            $config->getDefinition()
                ->getPath()
        );

        $this->setBaseUri($api, $requester);

        return $api;
    }

    /**
     * @throws AuthenticationLoadingException
     * @throws AuthenticationException
     */
    private function Authenticate(Config\Suite $config, Api $api, Requester $requester): Tokens
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
    private function getConfiguredPreparators(array $preparators, Tokens $tokens): array
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

    private function setBaseUri(Api $schema, Requester $requester): void
    {
        $baseUri = $schema->getServers()[0]
            ->getUrl()
        ;
        $requester->setBaseUri($baseUri);
    }
}
