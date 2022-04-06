<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Tokens;
use APITester\Definition\Operation;
use APITester\Definition\Security;
use APITester\Definition\Security\ApiKeySecurity;
use APITester\Definition\Security\HttpSecurity;
use APITester\Definition\Security\OAuth2\OAuth2Security;
use APITester\Definition\Token;
use APITester\Preparator\Config\PreparatorConfig;
use APITester\Preparator\Exception\InvalidPreparatorConfigException;
use APITester\Preparator\Exception\PreparatorLoadingException;
use APITester\Test\TestCase;
use APITester\Util\Json;
use APITester\Util\Object_;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

abstract class TestCasesPreparator
{
    protected Tokens $tokens;

    protected PreparatorConfig $config;

    public function __construct()
    {
        $this->tokens = new Tokens();
        $this->config = $this->newConfigInstance(static::getConfigFQCN());
    }

    public static function getName(): string
    {
        return mb_strtolower(
            str_replace(
                (new \ReflectionClass(self::class))->getShortName(),
                '',
                (new \ReflectionClass(static::class))->getShortName()
            )
        );
    }

    /**
     * @param string[] $excludedFields
     */
    public function buildTestCase(
        Operation $operation,
        RequestInterface $request,
        ResponseInterface $response,
        bool $auth = true,
        array $excludedFields = []
    ): TestCase {
        if ($auth) {
            $request = $this->authenticate($request, $operation);
        }

        return new TestCase($operation->getId(), $request, $response, $excludedFields);
    }

    /**
     * @throws PreparatorLoadingException
     *
     * @return iterable<array-key, TestCase>
     */
    public function prepare(Operations $operations): iterable
    {
        $testCases = $this->generateTestCases($operations);
        foreach ($testCases as $testCase) {
            $testCase->addExcludedFields($this->config->excludedFields);
        }

        return $testCases;
    }

    /**
     * @param array<mixed> $config
     *
     * @throws InvalidPreparatorConfigException
     */
    public function configure(array $config): void
    {
        try {
            $this->config = Object_::fromArray($config, static::getConfigFQCN());
        } catch (ExceptionInterface $e) {
            throw new InvalidPreparatorConfigException(static::class, 0, $e);
        }
    }

    public function setTokens(Tokens $tokens): self
    {
        $this->tokens = $tokens;

        return $this;
    }

    public function addToken(Token $token): self
    {
        $this->tokens->add($token);

        return $this;
    }

    public function getConfig(): PreparatorConfig
    {
        return $this->config;
    }

    /**
     * @return class-string<PreparatorConfig>
     */
    protected static function getConfigFQCN(): string
    {
        $configClass = __NAMESPACE__ . '\\Config\\' . static::getConfigClassName();
        if (!class_exists($configClass)) {
            $configClass = PreparatorConfig::class;
        }

        /** @var class-string<PreparatorConfig> */
        return $configClass;
    }

    protected static function getConfigClassName(): string
    {
        return str_replace(
            'TestCasesPreparator',
            '',
            (new \ReflectionClass(static::class))->getShortName(),
        );
    }

    protected function authenticate(RequestInterface $request, Operation $operation): RequestInterface
    {
        foreach ($operation->getSecurities() as $security) {
            $scopes = $security->getScopes()
                ->where('name', '!=', 'current_user')
                ->select('name')
                ->toArray()
            ;
            /** @var Token|null $token */
            $token = $this->tokens->where(
                'scopes',
                'includes',
                $scopes
            )->first()
            ;

            if (null !== $token) {
                return $this->setAuthentication($request, $security, $token);
            }
        }

        return $request;
    }

    protected function setAuthentication(RequestInterface $request, Security $security, Token $token): RequestInterface
    {
        if ($security instanceof HttpSecurity && $security->isBasic()) {
            $request = $request->withHeader(
                'Authorization',
                "Basic {$token->getAccessToken()}",
            );
        } elseif ($security instanceof HttpSecurity && $security->isBearer()) {
            $request = $request->withHeader(
                'Authorization',
                "Bearer {$token->getAccessToken()}",
            );
        } elseif ($security instanceof OAuth2Security) {
            $request = $request->withHeader(
                'Authorization',
                "Bearer {$token->getAccessToken()}",
            );
        } elseif ($security instanceof ApiKeySecurity) {
            $request = $this->addApiKeyToRequest(
                $request,
                $security,
                $token->getAccessToken(),
            );
        }

        return $request;
    }

    /**
     * @throws PreparatorLoadingException
     *
     * @return iterable<array-key, TestCase>
     */
    abstract protected function generateTestCases(Operations $operations): iterable;

    protected function generateRandomBody(Operation $operation): ?string
    {
        $request = $operation->getRequest('application/json');

        if (null === $request) {
            return null;
        }

        return Json::encode(
            $request->getBodyFromExamples() ?: (array) (new SchemaFaker(
                $request->getBody(),
                new Options(),
                true
            ))->generate()
        );
    }

    /**
     * @template T of PreparatorConfig
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function newConfigInstance(string $class)
    {
        return new $class();
    }

    private function addApiKeyToRequest(
        RequestInterface $request,
        ApiKeySecurity $security,
        string $apiKey
    ): RequestInterface {
        $newRequest = $request;
        if ('header' === $security->getIn()) {
            $newRequest = $request->withHeader($security->getKeyName(), $apiKey);
        } elseif ('cookie' === $security->getIn()) {
            $newRequest = $request->withHeader('Cookie', "{$security->getKeyName()}={$apiKey}");
        } elseif ('query' === $security->getIn()) {
            $oldUri = (string) $request->getUri();
            $prefix = str_contains($oldUri, '?') ? '&' : '?';
            $newRequest = $request->withUri(
                new Uri($oldUri . "{$prefix}{$security->getKeyName()}={$apiKey}")
            );
        }

        return $newRequest;
    }
}
