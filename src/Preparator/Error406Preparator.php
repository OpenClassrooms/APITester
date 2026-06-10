<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
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
                ->filter()
        )->flatten();
    }

    private function prepareTestCase(Operation $operation, string $type): ?TestCase
    {
        $example = $this->buildInvalidMediaTypeExample($operation, $type);
        $missingExampleReason = $this->getMissingRequiredExampleReason(
            $operation,
            $example,
            [Parameter::TYPE_PATH, Parameter::TYPE_QUERY, Parameter::TYPE_HEADER]
        );

        if ($missingExampleReason !== null) {
            $this->logger->warning(
                "Skipping 406 test for operation {$operation->getId()}: {$missingExampleReason}."
            );

            return null;
        }

        return $this->buildTestCase(
            $example
                ->setResponse(
                    ResponseExample::create()
                        ->setStatusCode('406')
                        ->setContent($this->config->response->body ?? null)
            ),
            false,
            [],
            false
        );
    }

    private function buildInvalidMediaTypeExample(Operation $operation, string $type): OperationExample
    {
        $example = $operation->getExamples()->count() === 0
            ? OperationExample::create('InvalidMediaType', $operation)
            : $operation->getExample()->withName('InvalidMediaType');

        $example
            ->setAutoComplete(false)
            ->setForceRandom(false)
        ;
        $this->completeConfiguredRequestParameters($example);

        return $example->setHeader('Accept', $type);
    }
}
