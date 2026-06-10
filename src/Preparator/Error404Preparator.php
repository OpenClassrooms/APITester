<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Response as DefinitionResponse;
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
        $testCase = $this->buildError404TestCase($response);

        return $testCase === null ? [] : [$testCase];
    }

    private function buildError404TestCase(DefinitionResponse $response): ?TestCase
    {
        $operation = $response->getParent();
        $example = $this->buildMissingResourceExample($operation);
        $this->completeConfiguredRequestParameters($example);

        $missingExampleReason = $this->getMissingRequiredExampleReason($operation, $example);

        if ($missingExampleReason !== null) {
            $this->logger->warning(
                "Skipping 404 test for operation {$operation->getId()}: {$missingExampleReason}."
            );

            return null;
        }

        return $this->buildTestCase(
            $example->setResponse(
                ResponseExample::create()
                    ->setStatusCode($this->config->response->getStatusCode() ?? '404')
                    ->setHeaders($this->config->response->headers ?? [])
                    ->setContent($this->config->response->body ?? $response->getDescription())
            ),
            false,
            [],
            false
        );
    }

    private function buildMissingResourceExample(Operation $operation): OperationExample
    {
        $example = $operation->getExamples()->count() === 0
            ? OperationExample::create('RandomPath', $operation)
            : $operation->getExample()->withName('RandomPath');

        return $example
            ->setAutoComplete(false)
            ->setForceRandom(false)
            ->setPathParameters($operation->getPathParameters()->getRandomExamples())
        ;
    }
}
