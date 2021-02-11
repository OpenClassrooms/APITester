<?php

namespace OpenAPITesting\Services\Test;

use OpenAPITesting\Models\Test\TestPlan;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ExecuteTestPlan
{
    private HttpClientInterface $client;

    public function execute(ExecuteTestPlanRequest $request): TestPlan
    {
        $testPlan = $request->getTestPlan();
        $this->initializeHTTPClient($testPlan);

        $asyncResponses = [];
        $operationTestCases = $testPlan->launch();
        foreach ($operationTestCases as $operationTestCase) {
            $requests = $operationTestCase->launch();
            $asyncResponses[$operationTestCase->getId()][] = $this->request(reset($requests));
        }

        $testPlan->finish($asyncResponses);

        return $testPlan;
    }

    private function initializeHTTPClient(TestPlan $testPlan)
    {
        $this->client = HttpClient::createForBaseUri($testPlan->getBaseUri());
    }

    private function request(RequestInterface $request): ResponseInterface
    {
        return $this->client->request($request->getMethod(), $request->getUri(), ['headers' => $request->getHeaders()]);
    }
}