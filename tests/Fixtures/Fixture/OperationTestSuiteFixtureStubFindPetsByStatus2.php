<?php

namespace OpenAPITesting\Tests\Fixtures\Fixture;

use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OperationStubFindPetsByStatus;

class OperationTestSuiteFixtureStubFindPetsByStatus2 extends OperationTestSuiteFixture
{
    public const OPERATION_ID = OperationStubFindPetsByStatus::OPERATION_ID;

    protected string $operationId = self::OPERATION_ID;
}