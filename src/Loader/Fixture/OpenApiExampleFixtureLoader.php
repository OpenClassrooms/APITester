<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader\Fixture;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use OpenAPITesting\Fixture\OpenApiTestPlanFixture;
use OpenAPITesting\Fixture\OperationTestCaseFixture;
use OpenAPITesting\Util\Json;

final class OpenApiExampleFixtureLoader
{
    public function __invoke(OpenApi $data): OpenApiTestPlanFixture
    {
        $testPlanFixture = new OpenApiTestPlanFixture();
        foreach ($data->paths as $path => $pathInfo) {
            foreach ($pathInfo->getOperations() as $method => $operation) {
                if (null === $operation->responses) {
                    continue;
                }

                $requests = $this->buildRequests($method, $operation, $path);
                $responses = $this->buildResponses($operation);
                $testCases = $this->buildTestCases($requests, $responses, $operation->operationId);

                $testPlanFixture->addMany($testCases);
            }
        }

        return $testPlanFixture;
    }

    private function buildRequests(string $method, Operation $operation, string $path): array
    {
        $requests = [];
        $requestBodies = $this->getRequestExampleBodies($operation->requestBody);
        foreach ($requestBodies as $expectedResponse => $body) {
            if (isset($requests[$expectedResponse])) {
                $requests[$expectedResponse]['body'] = $body;
            } else {
                $requests[$expectedResponse] = [
                    'path' => $path,
                    'method' => $method,
                    'body' => Json::encode($body),
                ];
            }
        }

        foreach ($operation->parameters as $parameter) {
            $examples = $this->getParameterExamples($parameter);
            foreach ($examples as $expectedResponse => $example) {
                if (isset($requests[$expectedResponse])) {
                    $requests[$expectedResponse] = $this->addParameterToRequest(
                        $parameter,
                        $example,
                        $requests[$expectedResponse]
                    );
                } else {
                    $newRequest = [
                        'path' => $path,
                        'method' => $method,
                    ];

                    $requests[$expectedResponse] = $this->addParameterToRequest(
                        $parameter,
                        $example,
                        $newRequest
                    );
                }
            }
        }

        return $this->redirectDefaultRequests($method, $requests);
    }


    private function buildResponses(Operation $operation)
    {
        $responses = [];
        foreach ($operation->responses as $statusCode => $response) {
            $examples = $this->getResponseExampleBodies($statusCode, $response);
            foreach ($examples as $label => $body) {
                if ($statusCode === 'default') {
                    $responses[$statusCode][$label] = [
                        'statusCode' => $body['code'],
                        'body' => Json::encode($body),
                    ];
                } elseif ($statusCode === $label) {
                    $responses[$statusCode] = [
                        'statusCode' => $statusCode,
                        'body' => Json::encode($body),
                    ];
                } else {
                    $responses[$statusCode][$label] = [
                        'statusCode' => $statusCode,
                        'body' => Json::encode($body),
                    ];
                }
            }

            foreach ($response->headers as $name => $value) {
                $responses[$statusCode][$label]['headers'][$name] = $value->example;
            }
        }

        return $responses;
    }

    private function buildTestCases(array $requests, array $responses, string $operationId)
    {
        $testCases = [];
        foreach ($requests as $key => $request) {
            $response = [];
            foreach (explode('.', explode('expects ', (string) $key)[1] ?? (string) $key) as $item) {
                $response = $response[$item] ?? $responses[$item];
            }
            if (!empty($response)) {
                $fixture = new OperationTestCaseFixture();

                $fixture->setOperationId($operationId);
                $fixture->setDescription((string) $key);
                $fixture->request = $request;
                $fixture->response = $response;
                $testCases[] = $fixture;
            }
        }

        return $testCases;
    }

    private function addParameterToRequest($parameter, $example, $request): array
    {
        if ($parameter->in === 'query') {
            $request['queryParameters'][$parameter->name] = $example;
        } elseif ($parameter->in === 'path') {
            $request['path'] = str_replace(
                sprintf('{%s}', $parameter->name),
                $example,
                $request['path']
            );
        } elseif ($parameter->in === 'header') {
            $request['headers'][$parameter->name] = $example;
        } elseif ($parameter->in === 'cookie') {
            $request['cookies'][$parameter->name] = $example;
        }

        return $request;
    }

    private function getParameterExamples($parameter): array
    {
        $examples = [];

        if ($parameter->schema->example) {
            $examples['default'] = $parameter->schema->example;
        }

        if ($parameter->examples) {
            foreach ($parameter->examples as $example) {
                $examples[$example->summary] = $example->value;
            }
        }

        return $examples;
    }

    /**
     * @param int|string $statusCode
     */
    private function getResponseExampleBodies($statusCode, Response $response): array
    {
        $bodies = [];
        if (isset($response->content['application/json']->example->value)) {
            $bodies[$statusCode] = $response->content['application/json']->example->value;
        }

        foreach ($response->content['application/json']->examples as $label => $example) {
            $bodies[$label] = $example->value;
        }

        return $bodies;
    }

    private function getRequestExampleBodies(?RequestBody $request = null): array
    {
        if (!$request) {
            return [];
        }

        $bodies = [];
        if (isset($request->content['application/json']->example->value)) {
            $bodies['default'] = $request->content['application/json']->example->value;
        }

        if ($request->content['application/json']->examples) {
            foreach ($request->content['application/json']->examples as $example) {
                $bodies[$example->summary] = $example->value;
            }
        }

        return $bodies;
    }

    private function redirectDefaultRequests(string $method, array $requests)
    {
        if (count($requests) === 1 && array_keys($requests)[0] === 'default') {
            $expectedResponse = '200';
            if ($method === 'post') {
                $expectedResponse = '201';
            }
            $requests[$expectedResponse] = $requests['default'];
            unset($requests['default']);
        }

        return $requests;
    }
}
