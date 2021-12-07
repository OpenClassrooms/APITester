<?php

declare(strict_types=1);

namespace OpenAPITesting\Fixture;

final class OpenApiTestPlanFixture
{
    /**
     * @var OperationTestCaseFixture[]
     */
    private array $operationTestCaseFixtures;

    /**
     * @param array<int, OperationTestCaseFixture> $operationTestCaseFixtures
     */
    public function __construct(array $operationTestCaseFixtures = [])
    {
        $this->operationTestCaseFixtures = $operationTestCaseFixtures;
    }

    /**
     * @param string|null $operationId
     *
     * @return array<int, OperationTestCaseFixture>
     */
    public function getOperationTestCaseFixtures(string $operationId = null): array
    {
        return array_filter(
            $this->operationTestCaseFixtures,
            static fn ($fixture) => $fixture->getOperationId() === $operationId
        );
    }

    public function add(OperationTestCaseFixture $fixture): self
    {
        $this->operationTestCaseFixtures[] = $fixture;

        return $this;
    }
}
