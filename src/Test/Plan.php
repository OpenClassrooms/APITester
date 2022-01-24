<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use OpenAPITesting\Authenticator\Authenticator;
use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Authenticator\Exception\AuthenticatorNotFoundException;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Loader\DefinitionLoader;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoaderNotFoundException;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException;
use OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Preparator\TestCasesPreparator;
use OpenAPITesting\Requester\Exception\RequesterNotFoundException;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Plan
{
    /**
     * @var TestCasesPreparator[]
     */
    private array $preparators;

    /**
     * @var Requester[]
     */
    private array $requesters;

    /**
     * @var DefinitionLoader[]
     */
    private array $loaders;

    /**
     * @var array<string, array<string, Result>>
     */
    private array $results = [];

    private LoggerInterface $logger;

    /**
     * @var Authenticator[]
     */
    private array $authenticators;

    /**
     * @param TestCasesPreparator[] $preparators
     * @param Requester[]           $requesters
     * @param DefinitionLoader[]    $definitionLoaders
     * @param Authenticator[]       $authenticators
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
     * @throws DefinitionLoaderNotFoundException
     * @throws ClientExceptionInterface
     * @throws DefinitionLoadingException
     * @throws RequesterNotFoundException
     * @throws InvalidPreparatorConfigException
     * @throws PreparatorLoadingException
     * @throws AuthenticatorNotFoundException
     * @throws AuthenticationLoadingException
     */
    public function execute(PlanConfig $testPlanConfig): void
    {
        foreach ($testPlanConfig->getTestSuiteConfigs() as $config) {
            $requester = $this->getRequester($config->getRequester());
            $definitionLoader = $this->getConfiguredLoader(
                $config->getDefinition()
                    ->getFormat()
            );
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
     * @return array<string, array<string, Result>>
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
     * @throws InvalidPreparatorConfigException
     *
     * @return TestCasesPreparator[]
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
     * @throws DefinitionLoaderNotFoundException
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

    private function setBaseUri(Api $schema, Requester $requester): void
    {
        $baseUri = $schema->getServers()[0]
            ->getUrl()
        ;
        $requester->setBaseUri($baseUri);
    }

    /**
     * @throws AuthenticatorNotFoundException
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
