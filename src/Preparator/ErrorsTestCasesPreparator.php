<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\SecurityScheme;
use Firebase\JWT\JWT;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

final class ErrorsTestCasesPreparator extends TestCasesPreparator
{
    public const HTTP_AUTH_TYPE = 'http';
    public const OAUTH2_AUTH_TYPE = 'oauth2';
    public const API_KEY_AUTH_TYPE = 'apikey';
    public const BASIC_AUTH_SCHEME = 'basic';
    public const BEARER_AUTH_SCHEME = 'bearer';
    public const FAKE_API_KEY = 'b85a985d-0114-4a23-8419-49f64a4c12f8';

    private array $handledErrors = [];

    private ?OpenApi $openApi = null;

    public static function getName(): string
    {
        return 'errors';
    }

    /**
     * @inheritDoc
     */
    public function prepare(OpenApi $openApi): array
    {
        $this->openApi = $openApi;

        /** @var TestCase[][] $testCases */
        $testCases = [];
        /** @var string $path */
        foreach ($this->openApi->paths as $path => $pathInfo) {
            $testCases[] = $this->prepareErrors($path, $pathInfo->getOperations());
        }

        return array_filter(array_merge(...$testCases));
    }

    public function configure(array $config): void
    {
        parent::configure($config);

        /** @var list<int> $include */
        $include = $config['include'] ?? [];
        /** @var list<int> $exclude */
        $exclude = $config['exclude'] ?? [];

        foreach ($this->getErrorPreparators() as $errorCode => $preparator) {
            if ([] !== $include && !\in_array($errorCode, $include, true)) {
                continue;
            }
            if ([] !== $exclude && \in_array($errorCode, $exclude, true)) {
                continue;
            }
            $this->handledErrors[$errorCode] = $preparator;
        }
    }

    private function prepare404(string $path, string $method, Operation $operation): ?TestCase
    {
        if (!isset($operation->responses) || !isset($operation->responses['404'])) {
            return null;
        }

        /** @var \cebe\openapi\spec\Response $response */
        $response = $operation->responses['404'];

        return new TestCase(
            $operation->operationId,
            new Request(
                mb_strtoupper($method),
                $this->processPath($path, $operation),
                [],
                $this->generateBody($operation),
            ),
            new Response(
                404,
                [],
                $response->description
            ),
            $this->getGroups($operation, $method),
        );
    }

    private function prepare401(string $path, string $method, Operation $operation): ?TestCase
    {
        if (!isset($operation->responses) || !isset($operation->responses['401'])) {
            return null;
        }

        $request = new Request(
            mb_strtoupper($method),
            $path . '?1=1'
        );

        $security = $this->getSecurity($operation);

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

        /** @var \cebe\openapi\spec\Response $response */
        $response = $operation->responses['401'];

        return new TestCase(
            $operation->operationId,
            $request,
            new Response(401, [], $response->description),
            $this->getGroups($operation, $method),
        );
    }

    private function processPath(string $path, Operation $operation): string
    {
        /** @var Parameter $parameter */
        foreach ($operation->parameters as $parameter) {
            if ('path' === $parameter->in) {
                $path = str_replace("{{$parameter->name}}", '-9999', $path);
            }
        }

        return $path;
    }

    private function generateBody(Operation $operation): ?string
    {
        if (!isset($operation->requestBody->content) || !isset($operation->requestBody->content['application/json'])) {
            return null;
        }
        /** @var Schema $schema */
        $schema = $operation->requestBody->content['application/json']->schema;

        return Json::encode((array) (new SchemaFaker($schema, new Options(), true))->generate());
    }

    /**
     * @return array<array-key, SecurityScheme>
     */
    private function getSecurity(Operation $operation): array
    {
        $formattedSecurity = [];

        if (!isset($this->openApi->components->securitySchemes)) {
            return $formattedSecurity;
        }

        /** @var SecurityScheme $scheme */
        foreach ($this->openApi->components->securitySchemes as $name => $scheme) {
            foreach ($operation->security as $security) {
                if (isset($security->{$name})) {
                    $formattedSecurity[$name] = $scheme;
                }
            }
        }

        return $formattedSecurity;
    }

    /**
     * @param array<array-key, SecurityScheme> $security
     */
    private function needsBasicCredentials(array $security): bool
    {
        return null !== $this->getNeededAuth($security, self::HTTP_AUTH_TYPE, self::BASIC_AUTH_SCHEME);
    }

    /**
     * @param array<array-key, SecurityScheme> $security
     */
    private function needsBearerToken(array $security): bool
    {
        return null !== $this->getNeededAuth($security, self::HTTP_AUTH_TYPE, self::BEARER_AUTH_SCHEME);
    }

    /**
     * @param array<array-key, SecurityScheme> $security
     */
    private function needsOAuth2Token(array $security): bool
    {
        return null !== $this->getNeededAuth($security, self::OAUTH2_AUTH_TYPE);
    }

    /**
     * @param array<array-key, SecurityScheme> $security
     */
    private function getNeededApiKey(array $security): ?SecurityScheme
    {
        return $this->getNeededAuth($security, self::API_KEY_AUTH_TYPE);
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

    private function addFakeBasicHeader(Request $request): Request
    {
        return $request->withAddedHeader('Authorization', 'Basic ' . base64_encode('aaaa:bbbbb'));
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

    private function addFakeOAuth2Token(Request $request): Request
    {
        return $this->addFakeBearerToken($request);
    }

    /**
     * @return array<int,callable(string,string,Operation):array<array-key,TestCase|null>>
     */
    private function getErrorPreparators(): array
    {
        return [
            401 => [$this, 'prepare401'],
            404 => [$this, 'prepare404'],
        ];
    }

    /**
     * @param Operation[] $operations
     *
     * @return TestCase[]
     */
    private function prepareErrors(string $path, array $operations): array
    {
        if ([] === $this->handledErrors) {
            return [];
        }

        $testCases = [];
        /** @var string $method */
        foreach ($operations as $method => $operation) {
            foreach ($this->handledErrors as $errorCode => $preparator) {
                if (!isset($operation->responses[$errorCode])) {
                    continue;
                }
                $result = $preparator($path, $method, $operation);

                $testCases[] = $result;
            }
        }

        return $testCases;
    }
}
