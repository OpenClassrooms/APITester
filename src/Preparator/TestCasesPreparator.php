<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Api;
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
    protected Tokens $tokens;

    /**
     * @throws PreparatorLoadingException
     *
     * @return iterable<array-key, TestCase>
     */
    abstract public function prepare(Api $api): iterable;

    /**
     * @param array<array-key, mixed> $rawConfig
     *
     * @throws InvalidPreparatorConfigException
     */
    public function configure(array $rawConfig): void
    {
        $this->tokens = new Tokens();
        if (isset($rawConfig['throw'])) {
            throw new InvalidPreparatorConfigException();
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

    abstract public static function getName(): string;

    /**
     * @return string[]
     */
    protected function getGroups(Operation $operation): array
    {
        return [
            $operation->getId(),
            $operation->getMethod(),
            ...$operation->getTags()
                ->select('name')
                ->toArray(),
            'preparator_' . static::getName(),
        ];
    }

    protected function authenticate(Request $request, Operation $operation): Request
    {
        foreach ($operation->getSecurities() as $security) {
            /** @var Token|null $token */
            $token = $this->tokens->where(
                'scopes',
                'includes',
                $security->getScopes()
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
