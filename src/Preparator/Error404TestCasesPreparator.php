<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
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
        /** @var TestCase[] */
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

        if (0 === $operation->getRequests()->count()) {
            $testcases[] = $this->buildTestCase(
                $operation,
                new Request(
                    $operation->getMethod(),
                    $operation->getRandomPath(),
                ),
                new Response(
                    $this->config->response->statusCode ?? 404,
                    $this->config->response->headers ?? [],
                    $this->config->response->body ?? $response->getDescription()
                ),
            );
        }

        foreach ($operation->getRequests() as $request) {
            $testcases[] = $this->buildTestCase(
                $operation,
                new Request(
                    $operation->getMethod(),
                    $operation->getRandomPath(),
                    [
                        'content-type' => $request->getMediaType(),
                    ],
                    $this->generateRandomBody($request),
                ),
                new Response(
                    $this->config->response->statusCode ?? 404,
                    $this->config->response->headers ?? [],
                    $this->config->response->body ?? $response->getDescription()
                ),
            );
        }

        return $testcases;
    }
}
