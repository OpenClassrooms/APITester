<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Test\TestCase;

final class Error403UserCentricPreparator extends TestCasesPreparator
{
    public function getStatusCode(): string
    {
        return '403';
    }
    /**
     * @inheritDoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var iterable<array-key, TestCase> */
        return $operations
            ->filter(fn ($operation) => $operation->isUserCentric())
            ->values()
            ->map(function ($operation) {
                return $this->prepareTestCase($operation);
            })
            ->flatten()
        ;
    }

    /**
     * @return array<TestCase>
     */
    private function prepareTestCase(Operation $operation): array
    {
        $testcases = [];

        if ($operation->getRequestBodies()->count() === 0) {
            $testcases[] = $this->buildTestCase(
                OperationExample::create('RandomPath', $operation)
                    ->setForceRandom()
                    ->setResponse(
                        ResponseExample::create()
                            ->setStatusCode($this->config->response->getStatusCode() ?? '403')
                            ->setHeaders($this->config->response->headers ?? [])
                            ->setContent($this->config->response->body ?? null)
                    )
            );
        }

        foreach ($operation->getRequestBodies() as $ignored) {
            $testcases[] = $this->buildTestCase(
                OperationExample::create('RandomPath', $operation)
                    ->setForceRandom()
                    ->setResponse(
                        ResponseExample::create()
                            ->setStatusCode($this->config->response->getStatusCode() ?? '403')
                            ->setHeaders($this->config->response->headers ?? [])
                            ->setContent($this->config->response->body ?? null)
                    )
            );
        }

        return $testcases;
    }
}
