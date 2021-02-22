<?php

namespace OpenAPITesting\Fixture;

class OpenApiTestPlanFixture
{
    /**
     * @var \OpenAPITesting\Fixture\OperationTestCaseFixture[]
     */
    protected array $operationTestCaseFixtures = [];

    public function __construct(array $operationTestCaseFixtures = [])
    {
        $this->operationTestCaseFixtures = $operationTestCaseFixtures;
    }

    public function getOperationTestCaseFixtures(string $operationId = null): array
    {
        return array_filter(
            $this->operationTestCaseFixtures,
            static fn ($fixture) => $fixture->getOperationId() === $operationId
        );
    }

    public function add(OperationTestCaseFixture $fixture): OpenApiTestPlanFixture
    {
        $this->operationTestCaseFixtures[] = $fixture;

        return $this;
    }
}
