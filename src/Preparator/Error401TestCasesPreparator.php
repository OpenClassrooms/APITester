<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use cebe\openapi\spec\SecurityScheme;
use Firebase\JWT\JWT;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Test\TestCase;

final class Error401TestCasesPreparator extends TestCasesPreparator
{
    public const HTTP_AUTH_TYPE = 'http';
    public const OAUTH2_AUTH_TYPE = 'oauth2';
    public const API_KEY_AUTH_TYPE = 'apikey';
    public const BASIC_AUTH_SCHEME = 'basic';
    public const BEARER_AUTH_SCHEME = 'bearer';
    public const FAKE_API_KEY = 'b85a985d-0114-4a23-8419-49f64a4c12f8';

    public static function getName(): string
    {
        return '401';
    }

    /**
     * @inheritDoc
     */
    public function prepare(Api $api): iterable
    {
        /** @var Responses $responses */
        $responses = $api->getOperations()
            ->select('responses.*')
            ->flatten()
            ->where('statusCode', 401)
            ->values();

        return $responses
            ->map(fn (DefinitionResponse $response) => $this->prepareTestCase($response))
        ;
    }

    private function prepareTestCase(DefinitionResponse $response): TestCase
    {
        $operation = $response->getOperation();
        $request = $this->setAuthentication(
            new Request(
                $operation->getMethod(),
                $operation->getPath()
            ),
            $operation->getSecurity()
        );

        return new TestCase(
            $operation->getId(),
            $request,
            new Response(401, [], $response->getDescription()),
            $this->getGroups($operation),
        );
    }

    /**
     * @param SecurityScheme[] $security
     */
    private function setAuthentication(Request $request, Security $security): Request
    {
        if ($this->needsBasicCredentials($security)) {
            $request = $this->addFakeBasicHeader($request);
        } elseif ($this->needsBearerToken($security)) {
            $request = $this->addFakeBearerToken($request);
        } elseif ($this->needsOAuth2Token($security)) {
            $request = $this->addFakeOAuth2Token($request);
        }

        $apiKey = $this->getNeededApiKey($security);
        if (null !== $apiKey) {
            $request = $this->addFakeApiKeyToRequest($apiKey, $request);
        }

        return $request;
    }

    /**
     * @param array<array-key, SecurityScheme> $security
     */
    private function needsBasicCredentials(array $security): bool
    {
        return null !== $this->getNeededAuth($security, self::HTTP_AUTH_TYPE, self::BASIC_AUTH_SCHEME);
    }

    private function addFakeBasicHeader(Request $request): Request
    {
        return $request->withAddedHeader('Authorization', 'Basic ' . base64_encode('aaaa:bbbbb'));
    }

    /**
     * @param array<array-key, SecurityScheme> $security
     */
    private function needsBearerToken(array $security): bool
    {
        return null !== $this->getNeededAuth($security, self::HTTP_AUTH_TYPE, self::BEARER_AUTH_SCHEME);
    }

    private function addFakeBearerToken(Request $request): Request
    {
        return $request->withAddedHeader(
            'Authorization',
            'Bearer ' . JWT::encode([
                'test' => 1234,
            ], 'abcd')
        );
    }

    /**
     * @param array<array-key, SecurityScheme> $security
     */
    private function needsOAuth2Token(array $security): bool
    {
        return null !== $this->getNeededAuth($security, self::OAUTH2_AUTH_TYPE);
    }

    private function addFakeOAuth2Token(Request $request): Request
    {
        return $this->addFakeBearerToken($request);
    }

    /**
     * @param array<array-key, SecurityScheme> $security
     */
    private function getNeededApiKey(array $security): ?SecurityScheme
    {
        return $this->getNeededAuth($security, self::API_KEY_AUTH_TYPE);
    }

    private function addFakeApiKeyToRequest(SecurityScheme $security, Request $request): Request
    {
        $newRequest = $request;

        if ('header' === $security->in) {
            $newRequest = $request->withAddedHeader($security->name, self::FAKE_API_KEY);
        } elseif ('cookie' === $security->in) {
            $newRequest = $request->withAddedHeader('Cookie', $security->name . '=' . self::FAKE_API_KEY);
        } elseif ('query' === $security->in) {
            $newRequest = $request->withUri(
                new Uri(((string) $request->getUri()) . sprintf('&%s=%s', $security->name, self::FAKE_API_KEY))
            );
        }

        return $newRequest;
    }

    /**
     * @param array<array-key, SecurityScheme> $securityConfig
     */
    private function getNeededAuth(array $securityConfig, string $type, string $scheme = null): ?SecurityScheme
    {
        foreach ($securityConfig as $security) {
            if ($type === mb_strtolower($security->type)) {
                if (null === $scheme || $scheme === mb_strtolower($security->scheme)) {
                    return $security;
                }
            }
        }

        return null;
    }
}
