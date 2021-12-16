<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use DateTimeInterface;
use GuzzleHttp\Psr7\ServerRequest;
use OpenAPITesting\Fixture\OperationTestCaseFixture;
use OpenAPITesting\Requester;
use OpenAPITesting\Test;
use OpenAPITesting\Util\Assert;

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

    /**
     * @var string[][]
     */
    private array $errors = [];

    private ?DateTimeInterface $finishedAt = null;

    private OperationTestCaseFixture $fixture;

    private TestSuite $parent;

    private ?DateTimeInterface $startedAt = null;

    public function __construct(
        TestSuite $parent,
        OperationTestCaseFixture $operationTestCaseFixture
    ) {
        $this->parent = $parent;
        $this->fixture = $operationTestCaseFixture;
    }

    public function getDescription(): string
    {
        return $this->fixture->getOperationId() . ' > ' . $this->fixture->getDescription() ?? 'test';
    }

    /**
     * @return string[][]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function launch(Requester $requester): void
    {
        $this->startedAt = Carbon::now();
        $response = $requester->request($this->getRequest());
        $this->errors = Assert::assertResponse($response, $this->fixture->getExpectedResponse());
        $this->finishedAt = Carbon::now();
    }

    private function getRequest(): ServerRequest
    {
        return new ServerRequest(
            $this->getRequest()
                ->getMethod(),
            "{$this->parent->getBaseUri()}/{$this->getRequest()->getUri()}",
            $this->fixture->getRequest()
                ->getHeaders(),
            $this->fixture->getRequest()
                ->getBody()
        );
    }
}
