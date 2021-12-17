<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use DateTimeInterface;
use OpenAPITesting\Requester;
use OpenAPITesting\Test;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @internal
 * @coversNothing
 */
final class TestSuite implements Test
{
    private ?DateTimeInterface $startedAt = null;

    private ?DateTimeInterface $finishedAt = null;

    /**
     * @var TestCase[]
     */
    private array $testCases;

    /**
     * @param TestCase[] $testCases
     */
    public function __construct(array $testCases)
    {
        $this->testCases = $testCases;
    }

    /**
     * @return array<string, ExpectationFailedException>
     */
    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->testCases as $testCase) {
            if (null !== $testCase->getErrors()) {
                $errors[$testCase->getDescription()] = $testCase->getErrors();
            }
        }

        return $errors;
    }

    public function launch(Requester $requester): void
    {
        $this->startedAt = Carbon::now();
        foreach ($this->testCases as $testCase) {
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
}
