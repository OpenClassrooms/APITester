<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader\Fixture;

use cebe\openapi\spec\Header;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Fixture\OpenApiTestSuiteFixture;
use OpenAPITesting\Fixture\OperationTestCaseFixture;
use OpenAPITesting\Util\Json;

final class OpenApiExampleFixtureLoader
{
    public function __invoke(OpenApi $data): OpenApiTestSuiteFixture
    {
        $testPlanFixture = new OpenApiTestSuiteFixture();
        /** @var string $path */
        foreach ($data->paths as $path => $pathInfo) {
            /** @var string $method */
            foreach ($pathInfo->getOperations() as $method => $operation) {
                if (null === $operation->responses) {
                    continue;
                }
                $requests = $this->buildRequests($operation, $method, $path);
                $responses = $this->buildResponses($operation);
                $testCases = $this->buildTestCases($operation->operationId, $requests, $responses);

                $testPlanFixture->addMany($testCases);
            }
        }

        return $testPlanFixture;
    }

    /**
     * @return array<string, Request>
     */
    private function buildRequests(Operation $operation, string $method, string $path): array
    {
        $requests = [];
        if (null !== $operation->requestBody) {
            /** @var RequestBody $requestBody */
            $requestBody = $operation->requestBody;
            $examples = $this->getExamples($requestBody->content['application/json']);
            foreach ($examples as $expectedResponse => $body) {
                $requests[$expectedResponse] = new Request(
                    $method,
                    $path . '?1=1',
                    [],
                    Json::encode($body),
                );
            }
        }

        /** @var Parameter $parameter */
        foreach ($operation->parameters as $parameter) {
            /** @var array<string, array<string|int>> $examples */
            $examples = $this->getExamples($parameter);
            foreach ($examples as $expectedResponse => $example) {
                if (! isset($requests[$expectedResponse])) {
                    $requests[$expectedResponse] = new Request($method, $path, []);
                }
                $this->addParameterToRequest(
                    $requests[$expectedResponse],
                    $parameter,
                    (string) $example[0],
                );
            }
        }

        return $requests;
    }

    /**
     * @return array<string, Response>
     */
    private function buildResponses(Operation $operation): array
    {
        if (! isset($operation->responses)) {
            return [];
        }
        $responses = [];
        foreach ($operation->responses as $statusCode => $response) {
            /** @var MediaType $content */
            $content = $response->content['application/json'];
            $examples = $this->getExamples($content, (string) $statusCode);
            foreach ($examples as $label => $body) {
                $key = $statusCode . '.' . $label;
                $responses[$key] = new Response(
                    (int) $statusCode,
                    [],
                    Json::encode($body)
                );
                /** @var Header $value */
                foreach ($response->headers as $name => $value) {
                    /** @var string $example */
                    $example = $value->example;
                    $responses[$key]->withAddedHeader($name, $example);
                }
            }
        }

        return $responses;
    }

    /**
     * @param array<string, Request>  $requests
     * @param array<string, Response> $responses
     *
     * @return OperationTestCaseFixture[]
     */
    private function buildTestCases(string $operationId, array $requests, array $responses): array
    {
        ksort($responses);
        $testCases = [];
        foreach ($requests as $key => $request) {
            if ('default' === $key) {
                $key = (string) array_key_first($responses);
            } else {
                $key = str_replace('expects ', '', $key);
            }
            $fixture = new OperationTestCaseFixture(
                $operationId,
                $request,
                $responses[$key] ?? new Response(),
                $key,
            );
            $testCases[] = $fixture;
        }

        return $testCases;
    }

    private function addParameterToRequest(Request $request, Parameter $parameter, string $example): void
    {
        if ('query' === $parameter->in) {
            $request->withUri(
                new Uri(((string) $request->getUri()) . "&{$parameter->name}={$example}")
            );
        } elseif ('path' === $parameter->in) {
            $request->withUri(
                new Uri(
                    str_replace(
                        sprintf('{%s}', $parameter->name),
                        $example,
                        (string) $request->getUri()
                    )
                )
            );
        } else {
            $request->withAddedHeader($parameter->name, $example);
        }
    }

    /**
     * @param MediaType|Schema|Parameter $object
     *
     * @return array<string, mixed[]>
     */
    private function getExamples(object $object, string $defaultKey = 'default'): array
    {
        $examples = [];
        if (\is_object($object->example) && isset($object->example->value)) {
            $examples[$defaultKey] = (array) $object->example->value;
        }

        if (isset($object->example)) {
            $examples[$defaultKey] = (array) $object->example;
        }

        if (isset($object->schema->example)) {
            $examples[$defaultKey] = (array) $object->schema->example;
        }

        if (isset($object->examples)) {
            foreach ($object->examples as $example) {
                $examples[$example->summary] = (array) $example->value;
            }
        }

        return $examples;
    }
}
