<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use OpenAPITesting\Authenticator\Authenticator;
use OpenAPITesting\Authenticator\Exception\AuthenticationException;
use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Authenticator\Exception\AuthenticatorNotFoundException;
use OpenAPITesting\Config;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Tokens;
use OpenAPITesting\Definition\Loader\DefinitionLoader;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoaderNotFoundException;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException;
use OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Preparator\TestCasesPreparator;
use OpenAPITesting\Requester\Exception\RequesterNotFoundException;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Object_;
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
    private array $definitionLoaders;

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
        ?array $preparators = null,
        ?array $requesters = null,
        ?array $definitionLoaders = null,
        ?array $authenticators = null,
        ?LoggerInterface $logger = null
    ) {
        if (!\defined('PROJECT_DIR')) {
            \define('PROJECT_DIR', \dirname(__DIR__, 2));
        }
        $this->preparators = $preparators ?? Object_::getImplementations(TestCasesPreparator::class);
        $this->requesters = $requesters ?? Object_::getImplementations(Requester::class);
        $this->definitionLoaders = $definitionLoaders ?? Object_::getImplementations(DefinitionLoader::class);
        $this->authenticators = $authenticators ?? Object_::getImplementations(Authenticator::class);
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
     * @throws AuthenticationException
     */
    public function execute(Config\Plan $testPlanConfig, ?string $suiteName = null): void
    {
        foreach ($testPlanConfig->getSuites() as $suiteConfig) {
            if (null !== $suiteName && $suiteConfig->getName() !== $suiteName) {
                continue;
            }
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
            );
            $testSuite->setBeforeTestCaseCallbacks($suiteConfig->getBeforeTestCaseCallbacks());
            $testSuite->setAfterTestCaseCallbacks($suiteConfig->getAfterTestCaseCallbacks());
            $testSuite->launch();
            if (\count($testSuite->getResult()) > 0) {
                $this->results[$suiteConfig->getName()] = $testSuite->getResult();
            }
        }
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
     * @return array<string, array<string, Result>>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function addRequester(Requester $requester): self
    {
        $this->requesters[] = $requester;

        return $this;
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
     * @throws AuthenticatorNotFoundException
     * @throws AuthenticationException
     */
    private function Authenticate(Config\Suite $config, Api $api, Requester $requester): Tokens
    {
        $tokens = new Tokens();
        foreach ($config->getAuthentifications() as $authConf) {
            $authenticator = $this->getConfiguredAuthenticator($authConf);
            $tokens->add($authenticator->authenticate($authConf, $api, $requester));
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
        foreach ($this->preparators as $p) {
            $config = $preparators[$p::getName()] ?? null;
            if (null !== $config) {
                $p->configure(new Config\Preparator($config));
            }
            $p->setTokens($tokens);
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

    /**
     * @throws AuthenticatorNotFoundException
     */
    private function getConfiguredAuthenticator(Config\Auth $config): Authenticator
    {
        foreach ($this->authenticators as $authenticator) {
            if ($authenticator::getName() === $config->getType()) {
                return $authenticator;
            }
        }

        throw new AuthenticatorNotFoundException("Authenticator {$config->getType()} not found");
    }
}
