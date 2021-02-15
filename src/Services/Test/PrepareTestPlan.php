<?php

namespace OpenAPITesting\Services\Test;

use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Gateways\Fixture\OperationTestSuiteFixtureGateway;
use OpenAPITesting\Gateways\OpenAPI\OpenAPIGateway;
use OpenAPITesting\Gateways\OpenAPI\OperationGateway;
use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;
use OpenAPITesting\Models\OpenAPI\Operation;
use OpenAPITesting\Models\Test\PathTestSuite;
use OpenAPITesting\Models\Test\PathTestSuiteFactory;
use OpenAPITesting\Models\Test\TestPlanBuilder;

class PrepareTestPlan
{
    protected OpenAPIGateway $openAPIGateway;

    protected OperationGateway $operationGateway;

    protected OperationTestSuiteFixtureGateway $operationTestSuiteFixtureGateway;

    protected PathTestSuiteFactory $pathTestSuiteFactory;

    protected TestPlanBuilder $testPlanBuilder;

    public function __construct(OpenAPIGateway $openAPIGateway, OperationGateway $operationGateway, OperationTestSuiteFixtureGateway $operationTestSuiteFixtureGateway, PathTestSuiteFactory $pathTestSuiteFactory, TestPlanBuilder $testPlanBuilder)
    {
        $this->openAPIGateway = $openAPIGateway;
        $this->operationGateway = $operationGateway;
        $this->operationTestSuiteFixtureGateway = $operationTestSuiteFixtureGateway;
        $this->pathTestSuiteFactory = $pathTestSuiteFactory;
        $this->testPlanBuilder = $testPlanBuilder;
    }

    public function execute(PrepareTestPlanRequest $request)
    {
        $openAPI = $this->getOpenAPI($request->getOpenAPITitle(), $request->getVersion());

        $filters = $request->getFilters();
        $operations = $this->getOperations($filters);
        $operationTestSuiteFixtures = $this->getOperationTestSuiteFixtures($filters);
        $pathTestSuites = $this->createPathTestSuites($operations, $operationTestSuiteFixtures);

        return $this->testPlanBuilder
            ->create($openAPI)
            ->withPathTestSuites($pathTestSuites)
//            ->withNotFoundProcessor(todo)
//            ->withInputProcessor(todo)
//            ->withSecurityProcessor(todo)
            ->build();
    }

    private function getOpenAPI(string $title, string $version): OpenApi
    {
        return $this->openAPIGateway->find($title, $version);
    }

    /**
     * @param string[] $filters
     * @return Operation[]
     */
    private function getOperations(array $filters): array
    {
        return $this->operationGateway->findAll($filters);
    }

    /**
     * @param string[] $filters
     * @return OperationTestSuiteFixture[]
     */
    private function getOperationTestSuiteFixtures(array $filters): array
    {
        return $this->operationTestSuiteFixtureGateway->findAll($filters);
    }

    /**
     * @param Operation[] $operations
     * @param OperationTestSuiteFixture[] $operationTestSuiteFixtures
     * @return PathTestSuite[]
     */
    private function createPathTestSuites(array $operations, array $operationTestSuiteFixtures): array
    {
        return $this->pathTestSuiteFactory->createPathTestSuites($operations, $operationTestSuiteFixtures);
    }
}