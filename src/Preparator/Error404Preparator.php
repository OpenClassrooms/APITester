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
            ->filter(static fn (?TestCase $testCase) => $testCase !== null)
            ->values()
        ;
    }

    private function prepareTestCase(DefinitionResponse $response): ?TestCase
    {
        $operation = $response->getParent();

        if ($operation->getPathParameters()->count() === 0) {
            return null;
        }

        $base = $operation->getExample();

        $example = OperationExample::create('RandomPath', $operation)
            // Disable autocompletion to avoid injecting random (potentially
            // invalid) data into the request.
            ->setAutoComplete(false)
            // Only the path is randomized so the request targets a resource
            // that does not exist, which is what should trigger the 404.
            ->setPathParameters($operation->getPathParameters()->getRandomExamples())
            ->setQueryParameters($base->getQueryParameters())
            ->setHeaders($base->getHeaders())
            ->setResponse(
                ResponseExample::create()
                    ->setStatusCode($this->config->response->getStatusCode() ?? '404')
                    ->setHeaders($this->config->response->headers ?? [])
                    ->setContent($this->config->response->body ?? $response->getDescription())
            )
        ;

        $baseBody = $base->getBody();
        if ($baseBody !== null) {
            $example->setBodyContent($baseBody->getContent());
        }

        return $this->buildTestCase($example);
    }
}
