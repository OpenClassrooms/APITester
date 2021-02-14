<?php

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use cebe\openapi\spec\OpenApi;
use DateTimeInterface;
use OpenAPITesting\Fixture\OpenApiTestPlanFixture;
use OpenAPITesting\Test;
use OpenAPITesting\Requester;

class TestPlan implements Test
{
    protected ?DateTimeInterface $finishedAt = null;

    protected OpenApi $openApi;

    protected ?DateTimeInterface $startedAt = null;

    /**
     * @var \OpenAPITesting\Fixture\OpenApiTestPlanFixture
     */
    private OpenApiTestPlanFixture $fixture;

    /**
     * @var OperationTestCase[]
     */
    private array $operationTestCases;

    public function __construct(
        OpenApi $openApi,
        OpenApiTestPlanFixture $fixture,
        array $filters = []
    ) {
        $this->openApi = $openApi;
        $this->fixture = $fixture;
        $this->buildTestCases($filters);
    }

    protected function buildTestCases(array $filters = []): void
    {
        foreach ($this->openApi->paths as $pathName => $path) {
            foreach ($path->getOperations() as $method => $operation) {
                foreach ($this->fixture->getOperationTestCaseFixtures($operation->operationId) as $testCaseFixture) {
                    $this->operationTestCases[] = new OperationTestCase(
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

    public function getBaseUri(): string
    {
        return rtrim($this->openApi->servers[0]->url, '/');
    }

    /**
     * @var string[][]
     */
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
}
