<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Test\TestCase;

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
            ->values();

        /** @var TestCase[] */
        return $responses
            ->map(fn (DefinitionResponse $response) => $this->prepareTestCase($response))
            ->flatten();
    }

    private function prepareTestCase(DefinitionResponse $response): TestCase
    {
        $operation = $response->getParent();
        $params = array_fill(
            0,
            $response->getParent()->getPathParameters()->count(),
            -9999
        );
        $request = new Request(
            $operation->getMethod(),
            $operation->getPath($params),
            [],
            $this->generateRandomBody($operation),
        );

        $request = $this->authenticate(
            $request,
            $operation,
        );

        return new TestCase(
            $operation->getId() . '_404',
            $request,
            new Response(
                $this->config->response->statusCode ?? 404,
                $this->config->response->headers ?? [],
                $this->config->response->body ?? $response->getDescription()
            ),
        );
    }
}
