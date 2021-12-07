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
    public function load(OpenApi $data): OpenApiTestPlanFixture
    {
        $testPlanFixture = new OpenApiTestPlanFixture();
        foreach ($data->paths as $path) {
            foreach ($path->getOperations() as $operation) {
                $request = $this->buildRequest($operation);
                if (null === $operation->responses) {
                    continue;
                }
                foreach ($operation->responses as $response) {
                    $response = $this->buildResponse($response);
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
     * @return array{'headers'?: array<array-key, string>, 'body'?: string}
     */
    private function buildRequest(Operation $operation): array
    {
        return [];
    }

    /**
     * @return array{'statusCode'?: int, 'headers'?: array<array-key, string>, 'body'?: string}
     */
    private function buildResponse(Response $response): array
    {
        return [];
    }
}
