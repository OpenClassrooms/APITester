<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader\Fixture;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Response;
use OpenAPITesting\Fixture\OpenApiTestPlanFixture;
use OpenAPITesting\Fixture\OperationTestCaseFixture;
use OpenAPITesting\Loader;

class OpenApiExampleFixtureLoader implements Loader
{
    public function load($data): OpenApiTestPlanFixture
    {
        if (!$data instanceof OpenApi) {
            throw new \InvalidArgumentException('Please use OpenApiLoader before');
        }

        $testPlanFixture = new OpenApiTestPlanFixture();
        foreach ($data->paths as $path) {
            foreach ($path->getOperations() as $operation) {
                $request = $this->buildRequest($operation);
                foreach ($operation->responses as $response) {
                    $response = $this->buildResponse($response);
                    $fixture = new OperationTestCaseFixture();
                    $fixture->setRequest($request);
                    $fixture->setResponse($response);
                    $fixture->setOperationId($operation->operationId);
                    $testPlanFixture->add($fixture);
                }
            }
        }

        return $testPlanFixture;
    }

    private function buildRequest(Operation $operation): array
    {
        return [];
    }

    private function buildResponse(Response $response): array
    {
        dump($response);
        return [];
    }
}
