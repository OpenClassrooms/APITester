<?php

namespace OpenAPITesting\Tests\Fixtures\Fixture;

use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OperationStubUpdatePet;

class OperationTestSuiteFixtureStub1 extends OperationTestSuiteFixture
{
    public const OPERATION_ID = OperationStubUpdatePet::OPERATION_ID;

    protected string $operationId = self::OPERATION_ID;
}