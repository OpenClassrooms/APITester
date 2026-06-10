<?php

declare(strict_types=1);

namespace APITester\Test\Preparator;

use APITester\Schema\Entity\Collection\Operations;
use APITester\Schema\Entity\Example\OperationExample;
use APITester\Schema\Entity\Example\ResponseExample;
use APITester\Schema\Entity\Operation;
use APITester\Test\Entity\TestCase;
use APITester\Test\Preparator\Config\Error406PreparatorConfig;

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
        /** @var iterable<array-key, TestCase> */
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
        )->flatten();
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
                        ->setContent($this->config->response->body ?? null)
                ),
        );
    }
}
