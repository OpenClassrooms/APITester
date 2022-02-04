<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Tokens;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Definition\Security\ApiKeySecurity;
use OpenAPITesting\Definition\Security\HttpSecurity;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2Security;
use OpenAPITesting\Definition\Token;
use OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Test\TestCase;

abstract class TestCasesPreparator
{
    /**
     * @var string[]
     */
    protected array $filters = [];

    protected Tokens $tokens;

    /**
     * @var string[]
     */
    protected array $excludedFields = [];

    /**
     * @var string[]
     */
    protected array $allowedConfigKeys = [
        'excludedFields',
    ];

    public static function getName(): string
    {
        return mb_strtolower(
            str_replace(
                'TestCasesPreparator',
                '',
                (new \ReflectionClass(static::class))->getShortName()
            )
        );
    }

    public static function getConfigSchema(): string
    {
        return __DIR__ . '/Config/Schema/' . static::getName() . '.yml';
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
            $testCase->addExcludedFields($this->excludedFields);
        }

        return $testCases;
    }

    /**
     * @throws PreparatorLoadingException
     *
     * @return iterable<array-key, TestCase>
     */
    abstract protected function generateTestCases(Operations $operations): iterable;

    /**
     * @param array<array-key, mixed> $rawConfig
     *
     * @throws InvalidPreparatorConfigException
     */
    public function configure(array $rawConfig): void
    {
        $this->checkConfig($rawConfig);
        $this->tokens = new Tokens();
        if (isset($rawConfig['excludedFields']) && is_array($rawConfig['excludedFields'])) {
            $this->excludedFields = $rawConfig['excludedFields'];
        }
    }

    /**
     * @param array<array-key, mixed> $rawConfig
     *
     * @throws InvalidPreparatorConfigException
     */
    private function checkConfig(array $rawConfig): void
    {
        $diff = \array_diff(\array_keys($rawConfig), $this->allowedConfigKeys);
        if (count($diff) > 0) {
            throw new InvalidPreparatorConfigException('Not allowed keys: ' . implode(', ', $diff));
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

    /**
     * @param string[] $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    protected function authenticate(Request $request, Operation $operation): Request
    {
        foreach ($operation->getSecurities() as $security) {
            $scopes = $security->getScopes()
                ->where('name', '!=', 'current_user')
                ->select('name')
                ->toArray();
            /** @var Token|null $token */
            $token = $this->tokens->where(
                'scopes',
                'includes',
                $scopes
            )->first();

            if (null !== $token) {
                return $this->setAuthentication($request, $security, $token);
            }
        }

        return $request;
    }

    protected function setAuthentication(Request $request, Security $security, Token $token): Request
    {
        if ($security instanceof HttpSecurity && $security->isBasic()) {
            $request = $request->withAddedHeader(
                'Authorization',
                "Basic {$token->getAccessToken()}",
            );
        } elseif ($security instanceof HttpSecurity && $security->isBearer()) {
            $request = $request->withAddedHeader(
                'Authorization',
                "Bearer {$token->getAccessToken()}",
            );
        } elseif ($security instanceof OAuth2Security) {
            $request = $request->withAddedHeader(
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

    private function addApiKeyToRequest(Request $request, ApiKeySecurity $security, string $apiKey): Request
    {
        $newRequest = $request;
        if ('header' === $security->getIn()) {
            $newRequest = $request->withAddedHeader($security->getKeyName(), $apiKey);
        } elseif ('cookie' === $security->getIn()) {
            $newRequest = $request->withAddedHeader('Cookie', "{$security->getKeyName()}={$apiKey}");
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
