<?php

namespace OpenAPITesting\Models\Test;

use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;
use OpenAPITesting\Models\OpenAPI\Operation;

class OperationTestSuite
{
    protected Operation $operation;

    /**
     * @var OperationTestCase[]
     */
    protected array $operationTestCases = [];

    protected OperationTestSuiteFixture $operationTestSuiteFixture;

    public function __construct(Operation $operation, OperationTestSuiteFixture $operationTestSuiteFixture)
    {
        $this->operation = $operation;
        $this->operationTestSuiteFixture = $operationTestSuiteFixture;
    }

    /**
     * @return OperationTestCase[]
     */
    public function getTestCases(): array
    {
        return $this->operationTestCases;
    }

    public function getMethod(): string
    {
        return $this->operation->getMethod();
    }
}