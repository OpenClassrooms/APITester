<?php

declare(strict_types=1);

namespace OpenAPITesting\Fixture;

final class OpenApiTestPlanFixture
{
    /**
     * @var array<array-key, OperationTestCaseFixture>
     */
    private array $operationTestCaseFixtures;

    /**
     * @param array<array-key, OperationTestCaseFixture> $operationTestCaseFixtures
     */
    public function __construct(array $operationTestCaseFixtures = [])
    {
        $this->operationTestCaseFixtures = $operationTestCaseFixtures;
    }

    /**
     * @return array<array-key, OperationTestCaseFixture>
     */
    public function getOperationTestCaseFixtures(?string $operationId = null): array
    {
        if (!$operationId) {
            return $this->operationTestCaseFixtures;
        }

        return array_filter(
            $this->operationTestCaseFixtures,
            static fn($fixture) => $fixture->getOperationId() === $operationId
        );
    }

    public function add(OperationTestCaseFixture $fixture): self
    {
        $this->operationTestCaseFixtures[] = $fixture;

        return $this;
    }

    /**
     * @param array<OperationTestCaseFixture> $fixtures
     */
    public function addMany(array $fixtures): self
    {
        $this->operationTestCaseFixtures = array_merge($this->operationTestCaseFixtures, $fixtures);

        return $this;
    }
}
