<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use cebe\openapi\spec\OpenApi;
use DateTimeInterface;
use OpenAPITesting\Fixture\OpenApiTestPlanFixture;
use OpenAPITesting\Requester;
use OpenAPITesting\Test;

/**
 * @internal
 * @coversNothing
 */
final class TestSuite implements Test
{
    private ?DateTimeInterface $finishedAt = null;

    private OpenApi $openApi;

    private ?DateTimeInterface $startedAt = null;

    private OpenApiTestPlanFixture $fixture;

    /**
     * @var TestCase[]
     */
    private array $operationTestCases;

    public function __construct(OpenApi $openApi, OpenApiTestPlanFixture $fixture)
    {
        $this->openApi = $openApi;
        $this->fixture = $fixture;
        $this->buildTestCases();
    }

    public function getBaseUri(): string
    {
        return rtrim($this->openApi->servers[0]->url, '/');
    }

    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->operationTestCases as $testCase) {
            $errors[] = $testCase->getErrors();
        }

        return array_filter(array_merge(...$errors));
    }

    public function launch(Requester $requester): void
    {
        $this->startedAt = Carbon::now();
        foreach ($this->operationTestCases as $testCase) {
            $testCase->launch($requester);
        }
        $this->finishedAt = Carbon::now();
    }

    private function buildTestCases(): void
    {
        foreach ($this->openApi->paths as $pathName => $path) {
            foreach ($path->getOperations() as $method => $operation) {
                foreach ($this->fixture->getOperationTestCaseFixtures($operation->operationId) as $testCaseFixture) {
                    $this->operationTestCases[] = new TestCase(
                        $operation,
                        $pathName,
                        mb_strtoupper($method),
                        $this,
                        $testCaseFixture,
                    );
                }
            }
        }
    }
}
