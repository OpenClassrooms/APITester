<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Illuminate\Support\Collection;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Mime;

final class Error406TestCasesPreparator extends TestCasesPreparator
{
    private const INVALID_TEST_CASES_NUMBER = 3;

    /**
     * @inheritdoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        /** @var Collection<array-key, TestCase> $testCases */
        $testCases = collect();
        foreach ($operations as $operation) {
            $responses = $operation->getResponses()
                ->where('statusCode', 'in', [200, 201])
            ;
            /** @var Responses $responses */
            /** @var Collection<int, string> $mediaTypes */
            $mediaTypes = $responses->select('mediaType');
            $testCases = $testCases->merge(
                $mediaTypes
                    ->compare(Mime::TYPES)
                    ->random(self::INVALID_TEST_CASES_NUMBER)
                    ->map(fn (string $type) => $this->prepareTestCase(
                        $operation,
                        $type
                    ))
            );
        }

        return $testCases;
    }

    private function prepareTestCase(Operation $operation, string $type): TestCase
    {
        $request = new Request(
            $operation->getMethod(),
            $operation->getExamplePath(),
            [
                'Accept' => $type,
            ]
        );

        $request = $this->authenticate($request, $operation);

        return new TestCase(
            "{$type}_{$operation->getId()}",
            $request,
            new Response(406)
        );
    }
}
