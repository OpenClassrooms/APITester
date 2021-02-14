<?php

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use cebe\openapi\spec\Operation;
use DateTimeInterface;
use Nyholm\Psr7\Request;
use OpenAPITesting\Fixture\OperationTestCaseFixture;
use OpenAPITesting\Requester;
use OpenAPITesting\Test;
use OpenAPITesting\Util\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OperationTestCase implements Test
{
    public const STATUS_FAILED = 'failed';

    public const STATUS_LAUNCHED = 'launched';

    public const STATUS_NOT_LAUNCHED = 'not launched';

    public const STATUS_SUCCESS = 'success';

    protected ResponseInterface $actualResponse;

    /**
     * @var string[][]
     */
    protected array $errors = [];

    protected ?DateTimeInterface $finishedAt = null;

    protected OperationTestCaseFixture $fixture;

    protected Operation $operation;

    private string $method;

    private TestPlan $parent;

    private string $path;

    private ?DateTimeInterface $startedAt = null;

    public function __construct(
        Operation $operation,
        string $path,
        string $method,
        TestPlan $parent,
        OperationTestCaseFixture $operationTestCaseFixture
    ) {
        $this->parent = $parent;
        $this->operation = $operation;
        $this->path = $path;
        $this->method = $method;
        $this->fixture = $operationTestCaseFixture;
    }

    public function getDescription(): string
    {
        return $this->operation->operationId . ' - ' . $this->fixture->getDescription();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function getParent(): TestPlan
    {
        return $this->parent;
    }

    public function getStatus(): string
    {
        if ($this->finishedAt !== null) {
            return count($this->errors) === 0 ? self::STATUS_SUCCESS : self::STATUS_FAILED;
        }
        if ($this->startedAt !== null) {
            return self::STATUS_LAUNCHED;
        }

        return self::STATUS_NOT_LAUNCHED;
    }

    public function launch(Requester $requester): void
    {
        $this->startedAt = Carbon::now();
        $response = $requester->request($this->getRequest());
        $this->errors = Assert::assertResponse($response, $this->fixture->getExpectedResponse());
        $this->finishedAt = Carbon::now();
    }

    private function getRequest(): RequestInterface
    {
        return new Request(
            $this->getMethod(),
            "{$this->parent->getBaseUri()}/{$this->getPath()}",
            $this->fixture->getRequestHeaders(),
            $this->fixture->getRequestBody()
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
