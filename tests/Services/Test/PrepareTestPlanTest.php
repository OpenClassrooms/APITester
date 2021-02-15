<?php

namespace OpenAPITesting\Tests\Services\Test;

use OpenAPITesting\Gateways\OpenAPI\OpenAPINotFoundException;
use OpenAPITesting\Models\Test\PathTestSuiteFactoryImpl;
use OpenAPITesting\Models\Test\TestPlanBuilderImpl;
use OpenAPITesting\Services\Test\PrepareTestPlan;
use OpenAPITesting\Services\Test\PrepareTestPlanRequest;
use OpenAPITesting\Tests\Fixtures\Fixture\OperationTestSuiteFixtureStubFindPetsByStatus1;
use OpenAPITesting\Tests\Fixtures\Fixture\OperationTestSuiteFixtureStubFindPetsByStatus2;
use OpenAPITesting\Tests\Fixtures\Fixture\OperationTestSuiteFixtureStubUpdatePet;
use OpenAPITesting\Tests\Fixtures\Gateways\OpenAPIGatewayMock;
use OpenAPITesting\Tests\Fixtures\Gateways\OperationGatewayMock;
use OpenAPITesting\Tests\Fixtures\Gateways\OperationTestSuiteFixtureGatewayMock;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OpenAPIStubPetStore;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OperationStubFindPetsByStatus;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OperationStubUpdatePet;
use PHPUnit\Framework\TestCase;

class PrepareTestPlanTest extends TestCase
{
    private PrepareTestPlan $prepareTestPlan;

    private PrepareTestPlanRequest $request;

    /**
     * @test
     */
    public function NonExistingOpenAPI_ThrowException()
    {
        $this->request->openAPITitle = 'non-existing';
        $this->expectException(OpenAPINotFoundException::class);
        $this->prepareTestPlan->execute($this->request);
    }

    /**
     * @test
     */
    public function WithoutOperation_ReturnTestPlanWithoutTestCases()
    {
        OperationGatewayMock::$operations = [];
        $testPlan = $this->prepareTestPlan->execute($this->request);
        $this->assertCount(0, $testPlan->launch());
    }

    /**
     * @test
     */
    public function WithoutAutomationWithoutFixtures_ReturnTestPlanWithoutTestCases()
    {
        OperationTestSuiteFixtureGatewayMock::$operationTestSuiteFixtures = [];
        $testPlan = $this->prepareTestPlan->execute($this->request);
        $this->assertCount(0, $testPlan->launch());
    }

    /**
     * @test
     */
    public function execute_ReturnTestPlan()
    {
        $testPlan = $this->prepareTestPlan->execute($this->request);
        $this->assertCount(3, $testPlan->launch());
    }

    protected function setUp(): void
    {
        $this->prepareTestPlan = new PrepareTestPlan(
            new OpenAPIGatewayMock([new OpenAPIStubPetStore()]),
            new OperationGatewayMock([new OperationStubFindPetsByStatus(), new OperationStubUpdatePet()]),
            new OperationTestSuiteFixtureGatewayMock([new OperationTestSuiteFixtureStubFindPetsByStatus1(), new OperationTestSuiteFixtureStubFindPetsByStatus2(), new OperationTestSuiteFixtureStubUpdatePet()]),
            new PathTestSuiteFactoryImpl(),
            new TestPlanBuilderImpl()
        );

        $this->request = new PrepareTestPlanRequest();
        $this->request->openAPITitle = OpenAPIStubPetStore::TITLE;
        $this->request->version = OpenAPIStubPetStore::VERSION;
        $this->request->filters = [];
    }
}