<?php

namespace OpenAPITesting\Tests\Fixtures\Models\Test;

use OpenAPITesting\Models\Test\OperationTestCase;
use OpenAPITesting\Tests\Fixtures\Models\Fixture\OperationTestCaseFixtureStubUpdatePet1;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OperationStubUpdatePet;

class OperationTestCaseStubUpdatePet1 extends OperationTestCase
{
    public function __construct()
    {
        parent::__construct(new OperationStubUpdatePet(), new OperationTestCaseFixtureStubUpdatePet1());
    }
}