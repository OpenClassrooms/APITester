<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Responses;
use APITester\Definition\Operation;
use APITester\Test\TestCase;
use APITester\Util\Mime;
use Illuminate\Support\Collection;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

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

        return $this->buildTestCase(
            $operation,
            $request,
            new Response(406)
        );
    }
}
