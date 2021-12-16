<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use DateTimeInterface;
use OpenAPITesting\Fixture\OpenApiTestSuiteFixture;
use OpenAPITesting\Requester;
use OpenAPITesting\Test;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @internal
 * @coversNothing
 */
final class TestSuite implements Test
{
    private string $baseUri;

    private OpenApiTestSuiteFixture $fixture;

    private ?DateTimeInterface $startedAt = null;

    private ?DateTimeInterface $finishedAt = null;

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
     * @return array<string, ExpectationFailedException>
     */
    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->operationTestCases as $testCase) {
            if (null !== $testCase->getErrors()) {
                $errors[$testCase->getDescription()] = $testCase->getErrors();
            }
        }

        return $errors;
    }

    public function launch(Requester $requester): void
    {
        $this->startedAt = Carbon::now();
        foreach ($this->operationTestCases as $testCase) {
            $testCase->launch($requester);
        }
        $this->finishedAt = Carbon::now();
    }

    public function getStartedAt(): ?DateTimeInterface
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?DateTimeInterface
    {
        return $this->finishedAt;
    }

    private function buildTestCases(): void
    {
        foreach ($this->fixture->getOperationTestCaseFixtures() as $testCaseFixture) {
            $this->operationTestCases[] = new TestCase($testCaseFixture);
        }
    }
}
