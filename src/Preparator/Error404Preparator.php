<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Test\TestCase;

final class Error404Preparator extends TestCasesPreparator
{
    public const INT32_MAX = 2147483647;

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
        $operation = $response->getParent();

        $testcases = [];

        $pathParameters = $operation->getPathParameters()
            ->map(static fn ($parameter) => $parameter->getName())
            ->toArray();

        $pathParameters = array_fill_keys($pathParameters, self::INT32_MAX);

        if ($operation->getRequestBodies()->count() === 0) {
            $testcases[] = $this->buildTestCase(
                $operation->getExample()
                    ->withName('RandomPath')
                    ->setPathParameters($pathParameters)
                    ->setResponse(
                        ResponseExample::create()
                            ->setStatusCode('404')
                            ->setHeaders($this->config->response->headers ?? [])
                            ->setContent($this->config->response->body ?? $response->getDescription())
                    )
            );
        }

        foreach ($operation->getRequestBodies() as $ignored) {
            $testcases[] = $this->buildTestCase(
                $operation->getExample()
                    ->withName('RandomPath')
                    ->setPathParameters($pathParameters)
                    ->setResponse(
                        ResponseExample::create()
                            ->setStatusCode('404')
                            ->setHeaders($this->config->response->headers ?? [])
                            ->setContent($this->config->response->body ?? $response->getDescription())
                    )
            );
        }

        return $testcases;
    }
}
