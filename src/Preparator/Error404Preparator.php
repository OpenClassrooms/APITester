<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Test\TestCase;

final class Error404Preparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var TestCase[] */
        return $operations
            ->select('responses.*')
            ->flatten()
            ->where('statusCode', 404)
            ->values()
            ->map(function ($response) {
                /** @var DefinitionResponse $response */
                return $this->prepareTestCase($response);
            })
            ->flatten()
        ;
    }

    /**
     * @return array<TestCase>
     */
    private function prepareTestCase(DefinitionResponse $response): array
    {
        $operation = $response->getParent();

        $testcases = [];

        if (0 === $operation->getRequestBodies()->count()) {
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
