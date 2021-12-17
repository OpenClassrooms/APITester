<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use DateTimeInterface;
use OpenAPITesting\Fixture\OperationTestCaseFixture;
use OpenAPITesting\Requester;
use OpenAPITesting\Test;
use OpenAPITesting\Util\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @internal
 * @coversNothing
 */
final class TestCase implements Test
{
    public const STATUS_FAILED = 'failed';

    public const STATUS_LAUNCHED = 'launched';

    public const STATUS_NOT_LAUNCHED = 'not launched';

    public const STATUS_SUCCESS = 'success';

    private ?ExpectationFailedException $errors = null;

    private OperationTestCaseFixture $fixture;

    private ?DateTimeInterface $startedAt = null;

    private ?DateTimeInterface $finishedAt = null;

    public function __construct(OperationTestCaseFixture $operationTestCaseFixture)
    {
        $this->fixture = $operationTestCaseFixture;
    }

    public function getDescription(): string
    {
        return ($this->fixture->getOperationId() ?? 'test') . ' > ' . ($this->fixture->getDescription() ?? 'test');
    }

    public function getErrors(): ?ExpectationFailedException
    {
        return $this->errors;
    }

    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function launch(Requester $requester): void
    {
        if ($this->fixture->getExpectedResponse() === null || $this->fixture->getRequest() === null) {
            throw new \InvalidArgumentException('No request or response found for fixture ' . $this->getDescription());
        }

        $this->startedAt = Carbon::now();
        $response = $requester->request($this->fixture->getRequest());
        $this->errors = Assert::assertObjectsEqual($response, $this->fixture->getExpectedResponse());
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
