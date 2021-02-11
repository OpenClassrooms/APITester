<?php

namespace OpenAPITesting\Services\Test;

use OpenAPITesting\Gateways\Fixture\OperationTestSuiteFixtureGateway;
use OpenAPITesting\Gateways\OpenAPI\OperationGateway;
use OpenAPITesting\Models\Test\OperationTestSuite;
use OpenAPITesting\Models\Test\TestPlan;

class PrepareTestPlan
{
    private OperationGateway $operationGateway;

    private OperationTestSuiteFixtureGateway $operationTestSuiteFixtureGateway;

    public function __construct(OperationGateway $operationGateway, OperationTestSuiteFixtureGateway $operationTestSuiteFixtureGateway)
    {
        $this->operationGateway = $operationGateway;
        $this->operationTestSuiteFixtureGateway = $operationTestSuiteFixtureGateway;
    }

    public function execute(PrepareTestPlanRequest $request)
    {
        $filters = $request->getFilters();

        $operations = $this->operationGateway->findAll($filters);
        $operationTestSuiteFixtures = $this->operationTestSuiteFixtureGateway->findAll($filters);

        $testPlan = new TestPlan();

        foreach ($operations as $operationId => $operation) {
            $testPlan->= new OperationTestSuite($operation, $operationTestSuiteFixtures[$operationId]);
        }
    }
}