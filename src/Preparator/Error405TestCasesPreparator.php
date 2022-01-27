<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
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
     * @var array<array-key, mixed>
     */
    private array $responseBody = [];

    public static function getName(): string
    {
        return '405';
    }

    /**
     * @inheritdoc
     */
    public function prepare(Api $api): iterable
    {
        $testCases = collect();
        /** @var Operations $operations */
        foreach ($api->getOperations()->groupBy('path', true) as $path => $operations) {
            $testCases = $testCases->merge(
                $operations
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

    public function configure(array $rawConfig): void
    {
        parent::configure($rawConfig);

        if (isset($rawConfig['responseBody']) && \is_array($rawConfig['responseBody'])) {
            $this->responseBody = $rawConfig['responseBody'];
        }
    }

    private function prepareTestCase(string $path, string $method): TestCase
    {
        return new TestCase(
            "{$method}_{$path}",
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
