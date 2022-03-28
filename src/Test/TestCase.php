<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use Nyholm\Psr7\Stream;
use OpenAPITesting\Definition\Request;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;
use OpenAPITesting\Util\Traits\TimeBoundTrait;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 */
final class TestCase extends \PHPUnit\Framework\TestCase
{
    use TimeBoundTrait;

    /**
     * @var \Closure[]
     */
    private array $afterCallbacks = [];

    /**
     * @var \Closure[]
     */
    private array $beforeCallbacks = [];

    /**
     * @var string[]
     */
    private array $excludedFields = [
        'headers',
        'reasonPhrase',
        'headerNames',
        'protocol',
    ];

    private ResponseInterface $expectedResponse;

    private string $id;

    private LoggerInterface $logger;

    private string $name;

    private RequestInterface $request;

    private ?Requester $requester = null;

    /**
     * @param string[] $excludedFields
     */
    public function __construct(
        string $name,
        RequestInterface $request,
        ResponseInterface $expectedResponse,
        array $excludedFields = []
    ) {
        parent::__construct($name);
        $this->request = $request;
        $this->expectedResponse = $expectedResponse;
        $this->name = $name;
        $this->logger = new NullLogger();
        $this->id = uniqid('testcase_', false);
        $this->excludedFields = [...$this->excludedFields, ...$excludedFields];
    }

    /**
     * @param string[] $excludedFields
     */
    public function addExcludedFields(array $excludedFields): void
    {
        /** @var string[] excludedFields */
        $this->excludedFields = array_merge($excludedFields, $this->excludedFields);
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setAfterCallbacks(array $callbacks): void
    {
        $this->afterCallbacks = $callbacks;
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setBeforeCallbacks(array $callbacks): void
    {
        $this->beforeCallbacks = $callbacks;
    }

    public function withAddedRequestBody(Request $request): self
    {
        return new self(
            $this->getName(),
            $this->getRequest()
                ->withBody(Stream::create(Json::encode($request->getBodyFromExamples()))),
            $this->getExpectedResponse()
        );
    }

    public function getName(bool $withDataSet = false): string
    {
        return $this->request->getMethod()
            . '_'
            . $this->request->getUri()
            . ' -> ' . $this->expectedResponse->getStatusCode();
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function setRequest(RequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getExpectedResponse(): ResponseInterface
    {
        return $this->expectedResponse;
    }

    public function assert(): void
    {
        $response = $this->requester->getResponse($this->id);
        $this->logger->log(LogLevel::DEBUG, 'received response: ' . $response->getBody());
        Assert::response(
            $this->expectedResponse,
            $response,
            $this->excludedFields
        );
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function prepare(): void
    {
        if (null === $this->requester) {
            throw new \RuntimeException("No requester configured for test '{$this->name}'.");
        }
        foreach ($this->beforeCallbacks as $callback) {
            ($callback)();
        }
        $this->startedAt = Carbon::now();
        $this->logger->log(
            LogLevel::DEBUG,
            Json::encode([
                'method' => $this->request->getMethod(),
                'url' => $this->request->getUri(),
                'body' => (string) $this->request->getBody(),
                'headers' => $this->request->getHeaders(),
                'expected_status' => $this->expectedResponse->getStatusCode(),
            ], JSON_PRETTY_PRINT)
        );
        $this->requester->request($this->request, $this->id);
        $this->finishedAt = Carbon::now();
        foreach ($this->afterCallbacks as $callback) {
            ($callback)();
        }
    }

    public function setRequester(?Requester $requester): void
    {
        $this->requester = $requester;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function withRequest(RequestInterface $request): self
    {
        $self = clone $this;
        $self->request = $request;

        return $self;
    }
}
