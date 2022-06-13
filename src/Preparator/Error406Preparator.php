<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Preparator\Config\Error406PreparatorConfig;
use APITester\Test\TestCase;

/**
 * @property Error406PreparatorConfig $config
 */
final class Error406Preparator extends TestCasesPreparator
{
    /**
     * @inheritdoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var TestCase[] */
        return $operations->map(
            fn (Operation $operation) => $operation->getResponses()
                ->select('mediaType')
                ->intersect($this->config->mediaTypes)
                ->compare($this->config->mediaTypes)
                ->shuffle()
                ->take($this->config->casesCount)
                ->sort()
                ->map(fn ($type) => $this->prepareTestCase(
                    $operation,
                    (string) $type
                ))
        )->flatten()
        ;
    }

    private function prepareTestCase(Operation $operation, string $type): TestCase
    {
        return $this->buildTestCase(
            OperationExample::create('InvalidMediaType', $operation)
                ->setHeaders([
                    'Accept' => $type,
                ])->setResponse(
                    ResponseExample::create()
                        ->setStatusCode('406')
                ),
        );
    }
}
