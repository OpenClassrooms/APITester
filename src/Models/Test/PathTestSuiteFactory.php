<?php

namespace OpenAPITesting\Models\Test;

use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;
use OpenAPITesting\Models\OpenAPI\Operation;

interface PathTestSuiteFactory
{
    /**
     * @param Operation[] $operations
     * @param OperationTestSuiteFixture[] $operationTestSuiteFixtures
     * @return PathTestSuite[]
     */
    public function createPathTestSuites(array $operations, array $operationTestSuiteFixtures): array;
}