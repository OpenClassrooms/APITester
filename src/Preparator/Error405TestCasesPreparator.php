<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

final class Error405TestCasesPreparator extends TestCasesPreparator
{
    public const SUPPORTED_HTTP_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'OPTIONS',
        'TRACE',
        'CONNECT',
    ];

    /**
     * @inheritdoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        $grouped = $operations
            ->where('responses.*.statusCode', 'contains', 405)
            ->groupBy('path', true)
        ;

        $testCases = collect();
        /** @var Operations $pathOperations */
        foreach ($grouped as $path => $pathOperations) {
            $testCases = $testCases->merge(
                $pathOperations
                    ->select('method')
                    ->compare(self::SUPPORTED_HTTP_METHODS)
                    ->map(fn ($method) => $this->prepareTestCase(
                        $path,
                        (string) $method
                    ))
            );
        }

        return $testCases;
    }

    private function prepareTestCase(string $path, string $method): TestCase
    {
        return new TestCase(
            "{$method}_{$path}_405",
            new Request(
                $method,
                $path
            ),
            new Response(
                405,
                [],
                [] !== $this->responseBody ? Json::encode($this->responseBody) : null,
            )
        );
    }
}
