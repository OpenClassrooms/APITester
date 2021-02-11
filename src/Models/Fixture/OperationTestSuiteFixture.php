<?php

namespace OpenAPITesting\Models\Fixture;

class OperationTestSuiteFixture
{
    protected string $operationId;

    /**
     * @var OperationTestCaseFixture[]
     */
    protected array $operationTestCaseFixtures = [];

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    /**
     * @param string $operationId
     */
    public function setOperationId(string $operationId): void
    {
        $this->operationId = $operationId;
    }

    /**
     * @return OperationTestCaseFixture[]
     */
    public function getOperationTestCaseFixtures(): array
    {
        return $this->operationTestCaseFixtures;
    }

    /**
     * @param OperationTestCaseFixture[] $operationTestCaseFixtures
     */
    public function setOperationTestCaseFixtures(array $operationTestCaseFixtures): void
    {
        $this->operationTestCaseFixtures = $operationTestCaseFixtures;
    }
}