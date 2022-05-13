<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Body;
use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Tokens;
use APITester\Definition\Example\OperationExample;
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
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
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

    /**
     * @param string[] $excludedFields
     */
    public function buildTestCase(OperationExample $example, bool $auth = true, array $excludedFields = []): TestCase
    {
        $operation = $example->getParent();
        $request = new Request(
            $example->getMethod(),
            $example->getPath(),
            $example->getHeaders(),
            $example->getStringBody(),
        );
        $response = new Response(
            $example->getResponse()
                ->getStatusCode(),
            $example->getResponse()
                ->getHeaders(),
            $example->getResponse()
                ->getStringContent(),
        );

        if ($auth) {
            $request = $this->authenticate($request, $operation);
        }

        return new TestCase(
            $operation->getId() . '/' . $example->getName(),
            $request,
            $response,
            $excludedFields,
        );
    }

    public static function getName(): string
    {
        return lcfirst(
            str_replace(
                str_replace('TestCases', '', (new \ReflectionClass(self::class))->getShortName()),
                '',
                (new \ReflectionClass(static::class))->getShortName()
            )
        );
    }

    /**
     * @throws PreparatorLoadingException
     *
     * @return iterable<array-key, TestCase>
     */
    public function getTestCases(Operations $operations): iterable
    {
        $testCases = $this->prepare($operations);
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
            'Preparator',
            'Config',
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
        $headers = $this->getAuthenticationParams($security, $token);
        foreach ($headers['headers'] ?? [] as $header => $value) {
            $request = $request->withHeader(
                $header,
                $value,
            );
        }

        if (isset($headers['query'])) {
            $oldUri = (string) $request->getUri();
            $prefix = str_contains($oldUri, '?') ? '&' : '?';
            $request = $request->withUri(
                new Uri($oldUri . $prefix . http_build_query($headers['query']))
            );
        }

        return $request;
    }

    /**
     * @return array{headers?: array<string, string>, query?: array<string, string>}
     */
    protected function getAuthenticationParams(Security $security, Token $token): array
    {
        if ($security instanceof HttpSecurity && $security->isBasic()) {
            return [
                'headers' => [
                    'Authorization' => "Basic {$token->getAccessToken()}",
                ],
            ];
        }
        if ($security instanceof HttpSecurity && $security->isBearer()) {
            return [
                'headers' => [
                    'Authorization' => "Bearer {$token->getAccessToken()}",
                ],
            ];
        }
        if ($security instanceof OAuth2Security) {
            return [
                'headers' => [
                    'Authorization' => "Bearer {$token->getAccessToken()}",
                ],
            ];
        }
        if ($security instanceof ApiKeySecurity) {
            if ('header' === $security->getIn()) {
                return [
                    'headers' => [
                        $security->getKeyName() => $token->getAccessToken(),
                    ],
                ];
            }
            if ('cookie' === $security->getIn()) {
                return [
                    'headers' => [
                        'Cookie' => "{$security->getKeyName()}={$token->getAccessToken()}",
                    ],
                ];
            }
            if ('query' === $security->getIn()) {
                return [
                    'query' => [
                        $security->getKeyName() => $token->getAccessToken(),
                    ],
                ];
            }
        }

        return [];
    }

    /**
     * @throws PreparatorLoadingException
     *
     * @return iterable<array-key, TestCase>
     */
    abstract protected function prepare(Operations $operations): iterable;

    protected function generateRandomBody(Body $request): ?string
    {
        return Json::encode(
            (array) (new SchemaFaker(
                $request->getSchema(),
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
}
