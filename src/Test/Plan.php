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
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

final class Plan
{
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

    private LoggerInterface $logger;

    /**
     * @param TestCasesPreparator[]     $preparators
     * @param class-string<Requester>[] $requesters
     * @param DefinitionLoader[]        $definitionLoaders
     */
    public function __construct(
        ?array $preparators = null,
        ?array $requesters = null,
        ?array $definitionLoaders = null,
        ?Authenticator $authenticator = null,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->preparators = $preparators ?? Object_::getImplementations(TestCasesPreparator::class);
        $this->requesters = $requesters ?? Object_::getImplementationsClasses(Requester::class);
        $this->definitionLoaders = $definitionLoaders ?? Object_::getImplementations(DefinitionLoader::class);
        $this->authenticator = $authenticator ?? new Authenticator();
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     * @throws InvalidPreparatorConfigException
     * @throws RequesterNotFoundException
     * @throws SuiteNotFoundException
     *
     * @return TestCase[]
     */
    public function getTestCases(Config\Plan $testPlanConfig, string $suiteName = '', array $options = []): array
    {
        $suiteConfig = $this->getSuiteConfig($testPlanConfig, $suiteName);

        $bootstrap = $testPlanConfig->getBootstrap();
        if ($bootstrap !== null) {
            require_once $bootstrap;
        }

        $kernel = $this->loadSymfonyKernel($suiteConfig);
        $definition = $this->loadApiDefinition($suiteConfig, $options);
        $requester = $this->loadRequester(
            $suiteConfig->getRequester(),
            $suiteConfig->getBaseUrl() ?? $definition->getUrl(),
            $kernel
        );
        $tokens = $this->authenticate($suiteConfig, $definition, $requester);
        $preparators = $this->loadPreparators($suiteConfig->getPreparators(), $tokens);

        return $this->prepareTestCases($suiteConfig, $definition, $preparators, $requester, $options);
    }

    /**
     * @throws SuiteNotFoundException
     */
    public function getSuiteConfig(Config\Plan $testPlanConfig, string $suiteName = ''): Config\Suite
    {
        $suites = $testPlanConfig->getSuites();
        if ($suiteName !== '') {
            /** @var Collection<string, Config\Suite> $indexSuites */
            $indexSuites = collect($suites)
                ->keyBy('name')
            ;
            if (!$indexSuites->has($suiteName)) {
                throw new SuiteNotFoundException();
            }

            /** @var Config\Suite $suite */
            $suite = $indexSuites->get($suiteName);

            return $suite;
        }

        if (\count($suites) === 0) {
            throw new SuiteNotFoundException();
        }

        return $suites[0];
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function loadSymfonyKernel(Config\Suite $suiteConfig): ?Kernel
    {
        $kernel = null;
        if ($suiteConfig->getSymfonyKernelClass() !== null) {
            $kernelClass = Object_::validateClass(
                $suiteConfig->getSymfonyKernelClass(),
                HttpKernelInterface::class
            );
            $kernel = $this->bootSymfonyKernel($kernelClass);
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
     * @param TestCasesPreparator[] $preparators
     * @param array<string, mixed>  $options
     *
     * @return TestCase[]
     */
    private function prepareTestCases(
        Config\Suite $suiteConfig,
        Api $api,
        array $preparators,
        Requester $requester,
        array $options = []
    ): array {
        $ignoreBaseline = !empty($options['ignore-baseline']);
        $onlyBaseline = !empty($options['only-baseline']);
        $part = isset($options['part']) ? (string) $options['part'] : null;

        /** @var Collection<int, TestCase> $allTests */
        $allTests = collect();

        foreach ($preparators as $preparator) {
            $preparator->setLogger($this->logger);
            $preparator->setSchemaValidationBaseline($suiteConfig->getFilters()->getSchemaValidationBaseline());

            $operations = $api->getOperations()
                ->map(static fn (Operation $op) => $op->setPreparator($preparator::getName()))
            ;

            try {
                $operations = $this->filterOperation($suiteConfig, $operations);
                $tests = $preparator->doPrepare($operations);

                if (!$ignoreBaseline) {
                    $tests = $this->filterTestCases($suiteConfig, $tests);
                }
                if ($onlyBaseline) {
                    $tests = $this->filterOnlyTestCases($suiteConfig, $tests);
                }

                foreach ($tests as $testCase) {
                    $testCase->setRequester($requester);
                    $testCase->setLogger($this->logger);
                    $testCase->setBeforeCallbacks($suiteConfig->getBeforeTestCaseCallbacks());
                    $testCase->setAfterCallbacks($suiteConfig->getAfterTestCaseCallbacks());
                    $testCase->setSpecification($api->getSpecification());
                    $allTests->add($testCase);
                }
            } catch (PreparatorLoadingException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        /** @var TestCase[] $sorted */
        $sorted = $allTests
            ->sortBy(static fn (TestCase $testCase) => $testCase->getOperation() ?? '')
            ->values()
            ->toArray()
        ;

        if ($part === null) {
            return $sorted;
        }

        $filtered = [];
        $total = \count($sorted);
        foreach ($sorted as $index => $testCase) {
            if ($this->indexInPart($part, $index, $total)) {
                $filtered[] = $testCase;
            }
        }

        return $filtered;
    }

    private function filterOperation(Config\Suite $suiteConfig, Operations $operations): Operations
    {
        return $operations->filter(
            static fn (Operation $operation) => $suiteConfig->getFilters()
                ->includes($operation)
        );
    }

    /**
     * @param iterable<array-key, TestCase> $tests
     *
     * @return iterable<array-key, TestCase>
     */
    private function filterTestCases(Config\Suite $suiteConfig, iterable $tests): iterable
    {
        $excludedTests = array_column(
            $this->toTestCaseFilter($suiteConfig->getFilters()->getBaseLineExclude()),
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
    private function filterOnlyTestCases(Config\Suite $suiteConfig, iterable $tests): iterable
    {
        $includedTests = array_column(
            $this->toTestCaseFilter($suiteConfig->getFilters()->getBaseLineExclude()),
            'name'
        );

        return collect($tests)->filter(static fn (TestCase $test) => \in_array(
            $test->getName(),
            $includedTests,
            true
        ));
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
                    ->filter(static fn ($value, $key) => str_starts_with($key, 'testcase.'))
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
}
