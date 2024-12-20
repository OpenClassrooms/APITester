<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Preparator\Config\Error405PreparatorConfig;
use APITester\Test\TestCase;
use Illuminate\Support\Collection;

/**
 * @property Error405PreparatorConfig $config
 */
final class Error405Preparator extends TestCasesPreparator
{
    /**
     * @inheritdoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var iterable<array-key, TestCase> */
        return $operations->groupBy('path', true)
            ->map(fn (Collection $pathOperations) => $pathOperations
                ->select('method')
                ->intersect($this->config->methods)
                ->compare($this->config->methods)
                ->crossJoin($pathOperations->take(1))
                ->map(
                    function (array $data) {
                        /** @var array{0: string, 1: Operation} $data */
                        return $this->prepareTestCase($data[1], $data[0]);
                    }
                ))
            ->flatten()
        ;
    }

    private function prepareTestCase(Operation $operation, string $method): TestCase
    {
        return $this->buildTestCase(
            OperationExample::create("UnsupportedMethod/{$method}", $operation)
                ->setMethod($method)
                ->setResponse(
                    ResponseExample::create()
                        ->setStatusCode($this->config->response->getStatusCode() ?? '405')
                        ->setContent($this->config->response->body ?? null)
                )
        );
    }
}
