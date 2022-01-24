<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Mime;

final class Error406TestCasesPreparator extends TestCasesPreparator
{
    private const INVALID_TEST_CASES_NUMBER = 3;

    public static function getName(): string
    {
        return '406';
    }

    /**
     * @inheritdoc
     */
    public function prepare(Api $api): iterable
    {
        $testCases = collect();
        foreach ($api->getOperations() as $operation) {
            /** @var Responses $responses */
            foreach ($operation->getResponses()->groupBy('statusCode') as $statusCode => $responses) {
                $testCases = $testCases->merge(
                    $responses
                        ->select('mediaType')
                        ->compare(Mime::TYPES)
                        ->random(self::INVALID_TEST_CASES_NUMBER)
                        ->map(fn (string $type) => $this->prepareTestCase(
                            $operation,
                            $statusCode,
                            $type
                        ))
                );
            }
        }

        return $testCases;
    }

    private function prepareTestCase(Operation $operation, int $statusCode, string $type): TestCase
    {
        return new TestCase(
            "{$type}_{$statusCode}_{$operation->getId()}",
            new Request(
                $operation->getMethod(),
                $operation->getPath(),
                [
                    'Accept' => $type,
                ]
            ),
            new Response(406)
        );
    }
}
