<?php

namespace OpenAPITesting\Tests\Services\Test;

use OpenAPITesting\Models\Test\TestPlan;
use OpenAPITesting\Services\Test\ExecuteTestPlan;
use OpenAPITesting\Services\Test\ExecuteTestPlanRequest;
use OpenAPITesting\Tests\Fixtures\ClientMock;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OpenAPIStubPetStore;
use PHPUnit\Framework\TestCase;

class ExecuteTestPlanTest extends TestCase
{
    private ExecuteTestPlan $executeTestPlan;

    private ExecuteTestPlanRequest $request;

    /**
     * @test
     */
    public function AllExcluded_ReturnTestSuite()
    {
        $testPlan = $this->executeTestPlan->execute($this->request);
        $this->assertNotNull($testPlan->getDuration());
        $this->assertCount(3, ClientMock::$requests);
    }

    protected function setUp(): void
    {
        $this->executeTestPlan = new ExecuteTestPlan(new ClientMock());
        $this->request = new ExecuteTestPlanRequest();
        $this->request->testPlan = new TestPlan(new OpenAPIStubPetStore());
    }
}
