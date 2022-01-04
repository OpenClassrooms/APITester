<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Definition\Loader\DefinitionLoader;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Plan
{
    /**
     * @var \OpenAPITesting\Test\Preparator\TestCasesPreparator[]
     */
    private array $preparators;

    /**
     * @var \OpenAPITesting\Requester\Requester[]
     */
    private array $requesters;

    /**
     * @var DefinitionLoader[]
     */
    private array $loaders;

    /**
     * @var array<string, array<string, \OpenAPITesting\Test\Result>>
     */
    private array $results = [];

    private LoggerInterface $logger;

    /**
     * @param \OpenAPITesting\Test\Preparator\TestCasesPreparator[] $preparators
     * @param \OpenAPITesting\Requester\Requester[]                 $requesters
     * @param DefinitionLoader[]                                    $loaders
     */
    public function __construct(
        array $preparators,
        array $requesters,
        array $loaders,
        ?LoggerInterface $logger = null
    ) {
        $this->preparators = $preparators;
        $this->requesters = $requesters;
        $this->loaders = $loaders;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @throws \OpenAPITesting\Test\LoaderNotFoundException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \OpenAPITesting\Definition\Loader\DefinitionLoadingException
     * @throws \OpenAPITesting\Test\RequesterNotFoundException
     * @throws \OpenAPITesting\Test\Preparator\InvalidPreparatorConfigException
     */
    public function execute(PlanConfig $testPlanConfig): void
    {
        foreach ($testPlanConfig->getTestSuiteConfigs() as $config) {
            $preparators = $this->getConfiguredPreparators($config->getPreparators());
            $loader = $this->getConfiguredLoader(
                $config->getDefinition()
                    ->getFormat()
            );
            /** @var \cebe\openapi\spec\OpenApi $schema needs replacement by proper domain object */
            $schema = $loader->load(
                $config->getDefinition()
                    ->getPath()
            );
            $requester = $this->getRequester($config->getRequester());
            $this->setBaseUri($schema, $requester);
            $testSuite = new Suite(
                $config->getName(),
                $schema,
                $preparators,
                $requester,
                $config->getFilters(),
                $this->logger,
            );
            $testSuite->setBeforeTestCaseCallback($config->getBeforeTestCaseCallback());
            $testSuite->setAfterTestCaseCallback($config->getAfterTestCaseCallback());
            $testSuite->launch();
            if (\count($testSuite->getResult()) > 0) {
                $this->results[$config->getName()] = $testSuite->getResult();
            }
        }
    }

    /**
     * @return array<string, array<string, \OpenAPITesting\Test\Result>>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @throws ExpectationFailedException
     */
    public function assert(): void
    {
        foreach ($this->getResults() as $suite) {
            foreach ($suite as $result) {
                Assert::true($result->hasSucceeded(), (string) $result);
            }
        }
    }

    /**
     * @param array<string, array<string, mixed>> $preparators
     *
     * @throws \OpenAPITesting\Test\Preparator\InvalidPreparatorConfigException
     *
     * @return \OpenAPITesting\Test\Preparator\TestCasesPreparator[]
     */
    private function getConfiguredPreparators(array $preparators): array
    {
        if (0 === \count($preparators)) {
            return $this->preparators;
        }

        $configuredPreparators = [];
        foreach ($this->preparators as $p) {
            $config = $preparators[$p::getName()] ?? null;
            if (null !== $config) {
                $p->configure($config);
            }
            if (\array_key_exists($p::getName(), $preparators)) {
                $configuredPreparators[] = $p;
            }
        }

        return $configuredPreparators;
    }

    /**
     * @throws \OpenAPITesting\Test\LoaderNotFoundException
     */
    private function getConfiguredLoader(string $format): DefinitionLoader
    {
        foreach ($this->loaders as $loader) {
            if ($loader::getFormat() === $format) {
                return $loader;
            }
        }

        throw new LoaderNotFoundException($format);
    }

    /**
     * @throws \OpenAPITesting\Test\RequesterNotFoundException
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

    private function setBaseUri(OpenApi $schema, Requester $requester): void
    {
        $baseUri = $schema->servers[0]->url;
        $requester->setBaseUri($baseUri);
    }
}
