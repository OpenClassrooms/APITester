<?php

namespace OpenAPITesting\Services\Test;

use OpenAPITesting\Models\Test\TestPlan;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ExecuteTestPlan
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function execute(ExecuteTestPlanRequest $request): TestPlan
    {
        $testPlan = $request->getTestPlan();

        $asyncResponses = [];
        $operationTestCases = $testPlan->launch();
        foreach ($operationTestCases as $operationTestCase) {
            $requests = $operationTestCase->launch();
            $asyncResponses[$operationTestCase->getId()][] = $this->request(reset($requests));
        }

        $testPlan->finish($asyncResponses);

        return $testPlan;
    }

    private function request(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request);
    }
}