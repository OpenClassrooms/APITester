<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use OpenAPITesting\Config\TestPlanConfig;
use OpenAPITesting\Definition\Loader\DefinitionLoader;
use OpenAPITesting\Requester\HttpRequester;
use OpenAPITesting\Test\Preparator\TestCasesPreparator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class TestPlan
{
    /**
     * @var \OpenAPITesting\Test\Preparator\TestCasesPreparator[]
     */
    private array $preparators;

    /**
     * @var DefinitionLoader[]
     */
    private array $loaders;

    /**
     * @var array<string, array<string, \OpenAPITesting\Test\TestError>>
     */
    private array $errors = [];

    private LoggerInterface $logger;

    /**
     * @param \OpenAPITesting\Test\Preparator\TestCasesPreparator[] $preparators
     * @param DefinitionLoader[]                                    $loaders
     */
    public function __construct(array $preparators, array $loaders, ?LoggerInterface $logger = null)
    {
        $this->preparators = $preparators;
        $this->loaders = $loaders;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @throws \OpenAPITesting\Test\LoaderNotFoundException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \OpenAPITesting\Definition\Loader\DefinitionLoadingException
     */
    public function execute(TestPlanConfig $testPlanConfig): void
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
            $testSuite = new TestSuite(
                $config->getTitle(),
                $schema,
                $preparators,
            );
            $testSuite->launch(new HttpRequester($schema->servers[0]->url), $this->logger);
            $this->errors[$config->getTitle()] = $testSuite->getErrors();
        }
    }

    /**
     * @return array<string, array<string, \OpenAPITesting\Test\TestError>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param string[] $preparators
     *
     * @return \OpenAPITesting\Test\Preparator\TestCasesPreparator[]
     */
    private function getConfiguredPreparators(array $preparators): array
    {
        return array_filter(
            $this->preparators,
            static fn (TestCasesPreparator $p) => \in_array(
                $p->getName(),
                $preparators,
                true,
            )
        );
    }

    /**
     * @throws \OpenAPITesting\Test\LoaderNotFoundException
     */
    private function getConfiguredLoader(string $format): DefinitionLoader
    {
        foreach ($this->loaders as $loader) {
            if ($loader->getFormat() === $format) {
                return $loader;
            }
        }

        throw new LoaderNotFoundException($format);
    }
}
