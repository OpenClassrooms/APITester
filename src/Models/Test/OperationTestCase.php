<?php

namespace OpenAPITesting\Models\Test;

use Carbon\Carbon;
use DateTime;
use Nyholm\Psr7\Request;
use OpenAPITesting\Models\Fixture\OperationTestCaseFixture;
use OpenAPITesting\Models\OpenAPI\Operation;
use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OperationTestCase
{
    use AssertionTrait;

    public const STATUS_FAILED = 'failed';

    public const STATUS_LAUNCHED = 'launched';

    public const STATUS_NOT_LAUNCHED = 'not launched';

    public const STATUS_SUCCESS = 'success';

    protected ResponseInterface $actualResponse;

    /**
     * @var string[][]
     */
    protected array $errors = [];

    protected ?DateTime $finishDate = null;

    protected Operation $operation;

    protected OperationTestCaseFixture $operationTestCaseFixture;

    private ?DateTime $startDate = null;

    public function __construct(Operation $operation, OperationTestCaseFixture $operationTestCaseFixture)
    {
        $this->operation = $operation;
        $this->operationTestCaseFixture = $operationTestCaseFixture;
    }

    /**
     * @return RequestInterface[]
     */
    public function launch(): array
    {
        $this->startDate = Carbon::now();

        return [$this->getRequest()];
    }

    private function getRequest(): RequestInterface
    {
        return new Request(
            mb_strtoupper($this->operation->getMethod()),
            $this->operation->getPath(),
            $this->operationTestCaseFixture->getRequestHeaders(),
            $this->operationTestCaseFixture->getRequestBody()
        );
    }

    /**
     * @param ResponseInterface[] $responses
     */
    public function finish(array $responses): array
    {
        if ($this->getStatus() !== self::STATUS_LAUNCHED) {
            throw new InvalidStatusException($this->getStatus());
        }
        foreach ($this->operationTestCaseFixture->getExpectedResponses() as $key => $expectedResponse) {
            $this->assertResponse($responses[$key], $expectedResponse);
        }

        $this->finishDate = Carbon::now();

        return $this->errors;
    }

    public function getStatus(): string
    {
        if ($this->finishDate !== null) {
            return count($this->errors) === 0 ? self::STATUS_SUCCESS : self::STATUS_FAILED;
        }
        if ($this->startDate !== null) {
            return self::STATUS_LAUNCHED;
        }

        return self::STATUS_NOT_LAUNCHED;
    }

    public function hasFail(): bool
    {
        return count($this->errors) !== 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getId(): string
    {
        return $this->operation->operationId;
    }
}