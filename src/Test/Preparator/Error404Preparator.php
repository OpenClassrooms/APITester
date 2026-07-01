<?php

declare(strict_types=1);

namespace APITester\Test\Preparator;

use APITester\Schema\Entity\Collection\Operations;
use APITester\Schema\Entity\Example\OperationExample;
use APITester\Schema\Entity\Example\ResponseExample;
use APITester\Schema\Entity\Response as DefinitionResponse;
use APITester\Test\Entity\TestCase;

final class Error404Preparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function prepare(Operations $operations): iterable
    {
        $testCases = [];

        foreach ($operations as $operation) {
            foreach ($operation->getResponses()->where('statusCode', 404) as $response) {
                $testCase = $this->prepareTestCase($response);
                if ($testCase !== null) {
                    $testCases[] = $testCase;
                }
            }
        }

        return $testCases;
    }

    private function prepareTestCase(DefinitionResponse $response): ?TestCase
    {
        $operation = $response->getParent();

        if ($operation->getPathParameters()->count() === 0) {
            return null;
        }

        $base = $operation->getExample();

        $example = OperationExample::create('RandomPath', $operation)
            ->setAutoComplete(false)
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
