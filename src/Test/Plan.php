<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Authenticator\Authenticator;
use OpenAPITesting\Authenticator\Exception\AuthenticatorNotFoundException;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Definition\Loader\DefinitionLoader;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoaderNotFoundException;
use OpenAPITesting\Requester\Exception\RequesterNotFoundException;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Plan
{
    /**
     * @var \OpenAPITesting\Preparator\TestCasesPreparator[]
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
     * @var \OpenAPITesting\Authenticator\Authenticator[]
     */
    private array $authenticators;

    /**
     * @param \OpenAPITesting\Preparator\TestCasesPreparator[] $preparators
     * @param \OpenAPITesting\Requester\Requester[]            $requesters
     * @param DefinitionLoader[]                               $definitionLoaders
     * @param Authenticator[]                                  $authenticators
     */
    public function __construct(
        array $preparators,
        array $requesters,
        array $definitionLoaders,
        array $authenticators = [],
        ?LoggerInterface $logger = null
    ) {
        $this->preparators = $preparators;
        $this->requesters = $requesters;
        $this->loaders = $definitionLoaders;
        $this->authenticators = $authenticators;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @throws \OpenAPITesting\Definition\Loader\Exception\DefinitionLoaderNotFoundException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException
     * @throws \OpenAPITesting\Requester\Exception\RequesterNotFoundException
     * @throws \OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException
     * @throws \OpenAPITesting\Preparator\Exception\PreparatorLoadingException
     * @throws \OpenAPITesting\Authenticator\Exception\AuthenticatorNotFoundException
     * @throws \OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException
     */
    public function execute(PlanConfig $testPlanConfig): void
    {
        foreach ($testPlanConfig->getTestSuiteConfigs() as $config) {
            $requester = $this->getRequester($config->getRequester());
            $definitionLoader = $this->getConfiguredLoader(
                $config->getDefinition()
                    ->getFormat()
            );
            /** @var \cebe\openapi\spec\OpenApi $schema needs replacement by proper domain object */
            $schema = $definitionLoader->load(
                $config->getDefinition()
                    ->getPath()
            );
            $this->setBaseUri($schema, $requester);
            $authConfig = $config->getAuth();
            $token = null;
            if (null !== $authConfig) {
                $authenticator = $this->getConfiguredAuthenticator($authConfig);
                $token = $authenticator->authenticate($authConfig, $schema, $requester);
            }
            $preparators = $this->getConfiguredPreparators($config->getPreparators(), $token);
            $testSuite = new Suite(
                $config->getName(),
                $schema,
                $preparators,
                $requester,
                $config->getFilters(),
                $this->logger,
            );
            $testSuite->setBeforeTestCaseCallbacks($config->getBeforeTestCaseCallbacks());
            $testSuite->setAfterTestCaseCallbacks($config->getAfterTestCaseCallbacks());
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
     * @throws \OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException
     *
     * @return \OpenAPITesting\Preparator\TestCasesPreparator[]
     */
    private function getConfiguredPreparators(array $preparators, ?string $token = null): array
    {
        if (0 === \count($preparators)) {
            return $this->preparators;
        }
        $configuredPreparators = [];
        foreach ($this->preparators as $p) {
            $config = $preparators[$p::getName()] ?? null;
            if (null !== $config) {
                $p->configure($config);
                $p->setToken($token);
            }
            if (\array_key_exists($p::getName(), $preparators)) {
                $configuredPreparators[] = $p;
            }
        }

        return $configuredPreparators;
    }

    /**
     * @throws \OpenAPITesting\Definition\Loader\Exception\DefinitionLoaderNotFoundException
     */
    private function getConfiguredLoader(string $format): DefinitionLoader
    {
        foreach ($this->loaders as $loader) {
            if ($loader::getFormat() === $format) {
                return $loader;
            }
        }

        throw new DefinitionLoaderNotFoundException($format);
    }

    /**
     * @throws \OpenAPITesting\Requester\Exception\RequesterNotFoundException
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

    /**
     * @throws \OpenAPITesting\Authenticator\Exception\AuthenticatorNotFoundException
     */
    private function getConfiguredAuthenticator(AuthConfig $config): Authenticator
    {
        foreach ($this->authenticators as $authenticator) {
            if ($authenticator::getName() === $config->getType()) {
                return $authenticator;
            }
        }

        throw new AuthenticatorNotFoundException("Authenticator {$config->getType()} not found");
    }
}
