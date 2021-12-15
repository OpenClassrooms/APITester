<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use DateTimeInterface;
use OpenAPITesting\Fixture\OpenApiTestSuiteFixture;
use OpenAPITesting\Requester;
use OpenAPITesting\Test;

/**
 * @internal
 * @coversNothing
 */
final class TestSuite implements Test
{
    private ?DateTimeInterface $finishedAt = null;

    private string $baseUri;

    private ?DateTimeInterface $startedAt = null;

    private OpenApiTestSuiteFixture $fixture;

    /**
     * @var TestCase[]
     */
    private array $operationTestCases = [];

    public function __construct(string $baseUri, OpenApiTestSuiteFixture $fixture)
    {
        $this->baseUri = $baseUri;
        $this->fixture = $fixture;
        $this->buildTestCases();
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * @return string[][]
     */
    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->operationTestCases as $testCase) {
            $errors[$testCase->getDescription()] = $testCase->getErrors();
        }

        return $errors;
//        return array_filter(array_merge(...$errors));
    }

    public function launch(Requester $requester): void
    {
        $this->startedAt = Carbon::now();
        foreach ($this->operationTestCases as $testCase) {
            $testCase->launch($requester);
        }
        $this->finishedAt = Carbon::now();
    }

    public function getFinishedAt(): ?DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function getStartedAt(): ?DateTimeInterface
    {
        return $this->startedAt;
    }

    private function buildTestCases(): void
    {
        foreach ($this->fixture->getOperationTestCaseFixtures() as $testCaseFixture) {
            $this->operationTestCases[] = new TestCase(
                $this,
                $testCaseFixture,
            );
        }
    }
}
