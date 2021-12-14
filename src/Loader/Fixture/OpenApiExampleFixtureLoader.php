<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader\Fixture;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use OpenAPITesting\Fixture\OpenApiTestPlanFixture;
use OpenAPITesting\Fixture\OperationTestCaseFixture;

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
                $testCases = $this->buildTestCases($requests, $responses);
                $testPlanFixture->addMany($testCases);
            }
        }
        dd($testPlanFixture);

        return $testPlanFixture;
    }

    private function buildRequests(string $method, Operation $operation, string $path): array
    {
        $requests = [];
        $requestBodyExamples = $this->extractExampleBodiesFromRequest($operation->requestBody);
        foreach ($requestBodyExamples as $expectedResponse => $bodyExample) {
            if (isset($requests[$expectedResponse])) {
                $requests[$expectedResponse]['body'] = $bodyExample;
            } else {
                $requests[$expectedResponse] = [
                    'path' => $path,
                    'method' => $method,
                    'body' => $bodyExample,
                ];
            }
        }

        foreach ($operation->parameters as $parameter) {
            $examples = $this->extractExamplesFromParameter($parameter);
            foreach ($examples as $expectedResponse => $example) {
                if (isset($requests[$expectedResponse])) {
                    $requests[$expectedResponse] = $this->assignExampleParameterToRequest(
                        $parameter,
                        $example,
                        $requests[$expectedResponse]
                    );
                } else {
                    $newRequest = [
                        'path' => $path,
                        'method' => $method,
                    ];

                    $requests[$expectedResponse] = $this->assignExampleParameterToRequest(
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
            $examples = $this->extractExampleBodiesFromResponse($statusCode, $response);
            foreach ($examples as $label => $body) {
                if ($statusCode === 'default') {
                    $responses[$statusCode][$label] = [
                        'statusCode' => $body['code'],
                        'body' => $body,
                    ];
                } else {
                    $responses[$statusCode][$label] = [
                        'statusCode' => $statusCode,
                        'body' => $body,
                    ];
                }
            }
        }

        return $responses;
    }

    private function buildTestCases(array $requests, array $responses)
    {
        $testCases = [];
        foreach ($requests as $key => $request) {
            $response = [];
            foreach (explode('.', (string) $key) as $item) {
                $response = $response[$item] ?? $responses[$item];
            }
            if (!empty($response)) {
                $fixture = new OperationTestCaseFixture();
                $fixture->request = $request;
                $fixture->response = $response;
                $testCases[] = $fixture;
            }
        }

        return $testCases;
    }

    private function assignExampleParameterToRequest($parameter, $example, $request): array
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

    private function extractExamplesFromParameter($parameter): array
    {
        $examples = [];

        if ($parameter->schema->example) {
            $examples['default'] = $parameter->schema->example;
        }

        if ($parameter->examples) {
            foreach ($parameter->examples as $example) {
                $examples[explode('expects ', $example->summary)[1]] = $example->value;
            }
        }

        return $examples;
    }

    /**
     * @param int|string $statusCode
     */
    private function extractExampleBodiesFromResponse($statusCode, Response $response): array
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

    private function extractExampleBodiesFromRequest(?RequestBody $request = null): array
    {
        if (!$request) {
            return [];
        }

        $bodies = [];
        if (isset($request->content['application/json']->example->value)) {
            $bodies['default'] = $request->content['application/json']->example->value;
        }

        if ($request->content['application/json']->examples) {
            foreach ($request->content['application/json']->examples as $label => $example) {
                $bodies[explode('expects ', $example->summary)[1]] = $example->value;
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
