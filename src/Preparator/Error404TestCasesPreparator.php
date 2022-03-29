<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Responses;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Test\TestCase;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error404TestCasesPreparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        /** @var Responses $responses */
        $responses = $operations
            ->select('responses.*')
            ->flatten()
            ->where('statusCode', 404)
            ->values()
        ;

        /** @var TestCase[] */
        return $responses
            ->map(fn (DefinitionResponse $response) => $this->prepareTestCase($response))
            ->flatten()
        ;
    }

    private function prepareTestCase(DefinitionResponse $response): TestCase
    {
        $operation = $response->getParent();
        $params = array_fill(
            0,
            $response->getParent()
                ->getPathParameters()
                ->count(),
            -9999
        );
        $request = new Request(
            $operation->getMethod(),
            $operation->getPath($params),
            [],
            $this->generateRandomBody($operation),
        );

        return $this->buildTestCase(
            $operation,
            $request,
            new Response(
                $this->config->response->statusCode ?? 404,
                $this->config->response->headers ?? [],
                $this->config->response->body ?? $response->getDescription()
            ),
        );
    }
}
