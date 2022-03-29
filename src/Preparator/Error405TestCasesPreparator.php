<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Operation;
use APITester\Test\TestCase;
use APITester\Util\Json;
use Illuminate\Support\Collection;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

/**
 * @property \APITester\Preparator\Config\Error405 $config
 */
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
            ->map(
                fn (Operation $operation) => [
                    'path' => $operation->getExamplePath(),
                    'method' => $operation->getMethod(),
                ]
            )
            ->groupBy('path', true)
        ;
        /** @var Collection<array-key, TestCase> $testCases */
        $testCases = collect();
        /** @var Operations $pathOperations */
        foreach ($grouped as $path => $pathOperations) {
            $testCases = $testCases->merge(
                $pathOperations
                    ->select('method')
                    ->compare(self::SUPPORTED_HTTP_METHODS)
                    ->intersect($this->config->methods ?: self::SUPPORTED_HTTP_METHODS)
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
            new Request(
                $method,
                $path
            ),
            new Response(
                $this->config->response->statusCode ?? 405,
                [],
                null !== $this->config->response ? Json::encode($this->config->response) : null,
            )
        );
    }
}
