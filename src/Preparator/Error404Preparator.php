<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Response;
use APITester\Test\TestCase;

final class Error404Preparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var iterable<array-key, TestCase> */
        return $operations
            ->map(
                fn (Operation $operation) => $operation->responses
                    ->where('statusCode', 404)
                    ->values()
                    ->map(fn (Response $response) =>  $this->prepareTestCase($operation, $response))
            )
            ->flatten()

            ->flatten()
        ;
    }

    /**
     * @return array<TestCase>
     */
    private function prepareTestCase(Operation $operation, Response $response): array
    {
        $testcases = [];

        if ($operation->getRequestBodies()->count() === 0) {
            $testcases[] = $this->buildTestCase(
                OperationExample::create('RandomPath', $operation)
                    ->setForceRandom()
                    ->setResponse(
                        ResponseExample::create()
                            ->setStatusCode($this->config->response->getStatusCode() ?? '404')
                            ->setHeaders($this->config->response->headers ?? [])
                            ->setContent($this->config->response->body ?? $response->getDescription())
                    )
            );
        }

        foreach ($operation->getRequestBodies() as $ignored) {
            $testcases[] = $this->buildTestCase(
                OperationExample::create('RandomPath', $operation)
                    ->setForceRandom()
                    ->setResponse(
                        ResponseExample::create()
                            ->setStatusCode($this->config->response->getStatusCode() ?? '404')
                            ->setHeaders($this->config->response->headers ?? [])
                            ->setContent($this->config->response->body ?? $response->getDescription())
                    )
            );
        }

        return $testcases;
    }
}
