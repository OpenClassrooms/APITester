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
    public const SUPPORTED_ERRORS = [404, 401];
    public const HTTP_AUTH_TYPE = 'http';
    public const OAUTH2_AUTH_TYPE = 'oauth2';
    public const API_KEY_AUTH_TYPE = 'apikey';
    public const BASIC_AUTH_SCHEME = 'basic';
    public const BEARER_AUTH_SCHEME = 'bearer';
    public const FAKE_API_KEY = 'b85a985d-0114-4a23-8419-49f64a4c12f8';

    /**
     * @var array<array-key, int>
     */
    private array $handledErrors = [];

    public static function getName(): string
    {
        return 'errors';
    }

    /**
     * @inheritDoc
     */
    public function prepare(OpenApi $openApi): array
    {
        $testCases = [];
        /** @var string $path */
        foreach ($openApi->paths as $path => $pathInfo) {
            /** @var string $method */
            foreach ($pathInfo->getOperations() as $method => $operation) {
                foreach ($this->handledErrors as $error) {
                    if (!isset($operation->responses[$error])) {
                        continue;
                    }
                    $testCases[] = $this->prepareError($error, $path, $method, $operation, $openApi);
                }
            }
        }

        return $testCases;
    }

    public function configure(array $config): void
    {
        parent::configure($config);

        $this->handledErrors = self::SUPPORTED_ERRORS;

        if (!empty($config['include'])) {
            $this->handledErrors = array_filter(
                $config['include'],
                static fn (int $it) => in_array($it, self::SUPPORTED_ERRORS, true)
            );
        }

        if (!empty($config['exclude'])) {
            $this->handledErrors = array_diff(
                $this->handledErrors,
                $config['exclude']
            );
        }
    }

    private function prepareError(
        int $error,
        string $path,
        string $method,
        Operation $operation,
        OpenApi $openApi
    ): ?TestCase {
        if (!in_array($error, self::SUPPORTED_ERRORS)) {
            throw new \InvalidArgumentException(sprintf('Error %d is not handled in the %s class.', $error, __CLASS__));
        }

        return $this->{'prepare' . $error}($path, $method, $operation, $openApi);
    }

    private function prepare404(string $path, string $method, Operation $operation, OpenApi $openApi): TestCase
    {
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

    private function prepare401(string $path, string $method, Operation $operation, OpenApi $openApi): TestCase
    {
        $request = new Request(
            mb_strtoupper($method),
            $path . '?1=1'
        );

        $security = $this->getSecurity($operation, $openApi);

        if ($this->needsBasicCredentials($security)) {
            $request = $this->addFakeBasicHeader($request);
        } elseif ($this->needsBearerToken($security)) {
            $request = $this->addFakeBearerToken($request);
        } elseif ($this->needsOAuth2Token($security)) {
            $request = $this->addFakeOAuth2Token($request);
        }

        $apiKey = $this->getNeededApiKey($security);
        if ($apiKey) {
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

    private function getSecurity(Operation $operation, OpenApi $openApi): array
    {
        $formattedSecurity = [];

        if (empty($openApi->components->securitySchemes)) {
            return $formattedSecurity;
        }

        foreach ($openApi->components->securitySchemes as $name => $scheme) {
            foreach ($operation->security as $security) {
                if (isset($security->$name)) {
                    $formattedSecurity[$name] = $scheme;
                }
            }
        }

        return $formattedSecurity;
    }

    private function needsBasicCredentials(array $security): bool
    {
        return null !== $this->getNeededAuth($security, self::HTTP_AUTH_TYPE, self::BASIC_AUTH_SCHEME);
    }

    private function needsBearerToken(array $security): bool
    {
        return null !== $this->getNeededAuth($security, self::HTTP_AUTH_TYPE, self::BEARER_AUTH_SCHEME);
    }

    private function needsOAuth2Token(array $security): bool
    {
        return null !== $this->getNeededAuth($security, self::OAUTH2_AUTH_TYPE);
    }

    private function getNeededApiKey(array $security): ?SecurityScheme
    {
        return $this->getNeededAuth($security, self::API_KEY_AUTH_TYPE);
    }

    private function getNeededAuth(array $securityConfig, string $type, string $scheme = null): ?SecurityScheme
    {
        /** @var \cebe\openapi\spec\SecurityScheme $security */
        foreach ($securityConfig as $security) {
            if ($type === strtolower($security->type)) {
                if (null === $scheme || $scheme === strtolower($security->scheme)) {
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
        return $request->withAddedHeader('Authorization', 'Bearer ' . JWT::encode(['test' => 1234], 'abcd'));
    }

    private function addFakeOAuth2Token(Request $request): Request
    {
        return $this->addFakeBearerToken($request);
    }
}
