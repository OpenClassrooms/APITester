<?php

namespace OpenAPITesting\Tests\Services\Test;

use OpenAPITesting\Models\Test\TestPlan;
use OpenAPITesting\Services\Test\ExecuteTestPlan;
use OpenAPITesting\Services\Test\ExecuteTestPlanRequest;
use PHPUnit\Framework\TestCase;

class ExecuteTestPlanTest extends TestCase
{
    private ExecuteTestPlan $testLauncher;

    /**
     * @test
     */
    public function AllExcluded_ReturnTestSuite()
    {
        $request = new ExecuteTestPlanRequest();
        $request->testPlan = new TestPlan();
        $testSuite = $this->testLauncher->execute($request);
        $this->assertEquals('Swagger Petstore - OpenAPI 3.0', $testSuite->openAPITitle);
    }

    protected function setUp(): void
    {
        $this->testLauncher = new ExecuteTestPlan();
    }
}
