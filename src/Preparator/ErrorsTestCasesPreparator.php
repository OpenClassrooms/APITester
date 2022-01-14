<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use Firebase\JWT\JWT;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

final class ErrorsTestCasesPreparator extends TestCasesPreparator
{
    public const SUPPORTED_ERRORS = [404, 401];

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
                    $testCases[] = $this->prepareError($error, $path, $method, $operation);
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

    private function prepare401(string $path, string $method, Operation $operation): TestCase
    {
        dd($this->generateFakeAuthHeader($operation));

        return new TestCase(
            $operation->operationId,
            new Request(
                mb_strtoupper($method),
                $path,
                [
                    'Authorization' => $this->generateFakeAuthHeader($operation),
                ],
            ),
            new Response(401),
            $this->getGroups($operation, $method),
        );
    }

    private function prepare404(string $path, string $method, Operation $operation): TestCase
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

    private function prepareError(int $error, string $path, string $method, Operation $operation): ?TestCase
    {
        if (!in_array($error, self::SUPPORTED_ERRORS)) {
            throw new \InvalidArgumentException(sprintf('Error %d is not handled in the %s class.', $error, __CLASS__));
        }

        return $this->{'prepare' . $error}($path, $method, $operation);
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

    private function generateFakeAuthHeader(Operation $operation)
    {
        if (in_array('OAuth2', $operation->tags)) {
            return 'Basic ' . base64_encode('aaaa:bbbbb');
        }

        return 'Bearer ' . JWT::encode(['test' => 1234], 'abcd');
    }
}
