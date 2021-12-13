<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader\Fixture;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
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
                foreach ($operation->responses as $case => $response) {
                    $request = $this->buildRequest($method, $case, $operation, $path);
                    $response = $this->buildResponse($case, $response);
                    $fixture = new OperationTestCaseFixture();
                    $fixture->request = $request;
                    $fixture->response = $response;
                    $fixture->setOperationId($operation->operationId);
                    $testPlanFixture->add($fixture);
                }
            }
        }

        return $testPlanFixture;
    }

    /**
     * @param int|string $case
     *
     * @throws \Exception
     *
     * @return array{'path': string, 'headers'?: array<array-key, string>, 'cookies'?:array<array-key, string>, 'body'?: string}
     */
    private function buildRequest(string $method, $case, Operation $operation, string $path): array
    {
        $request = [
            'path' => $path,
            'method' => $method,
        ];

        if ($operation->requestBody) {
            $requestBody = $operation->requestBody->content['application/json'];
            $request['body'] = $requestBody->example ?? $requestBody->examples[$case]->value;
        }

        foreach ($operation->parameters as $parameter) {
            $example = $parameter->schema->example ?? $parameter->examples[$case]->value ?? null;
            if (!$example) {
                if ($parameter->required) {
                    throw new \Exception(
                        sprintf('Parameter %s is required but it has no example value.', $parameter->name)
                    );
                }
                continue;
            }

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
        }

        return $request;
    }

    /**
     * @return array{'statusCode'?: int, 'headers'?: array<array-key, string>, 'body'?: string}
     */
    private function buildResponse($case, Response $openApiResponse): array
    {
        $response = [];

        if ($case !== 'default') {
            $response['statusCode'] = $case;
        }

        if (isset($openApiResponse->content['application/json']->example)) {
            $response['body'] = $openApiResponse->content['application/json']->example;
        }

        foreach ($openApiResponse->headers as $header => $value) {
            $response['headers'][$header] = $value->example;
        }

        return $response;
    }
}
