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
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 */
final class TestCase implements Test
{
    use TimeBoundTrait;

    private RequestInterface $request;

    private ResponseInterface $expectedResponse;

    private string $name;

    private ?Result $result = null;

    private ?Requester $requester = null;

    private LoggerInterface $logger;

    private string $id;

    /**
     * @var string[]
     */
    private array $excludedFields = [
        'headers',
        'reasonPhrase',
        'headerNames',
        'protocol',
    ];

    /**
     * @var \Closure[]
     */
    private array $beforeCallbacks = [];

    /**
     * @var \Closure[]
     */
    private array $afterCallbacks = [];

    /**
     * @param string[] $excludedFields
     */
    public function __construct(
        string $name,
        RequestInterface $request,
        ResponseInterface $expectedResponse,
        array $excludedFields = []
    ) {
        $this->request = $request;
        $this->expectedResponse = $expectedResponse;
        $this->name = $name;
        $this->logger = new NullLogger();
        $this->id = uniqid('testcase_', false);
        $this->excludedFields = [...$this->excludedFields, ...$excludedFields];
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setBeforeCallbacks(array $callbacks): void
    {
        $this->beforeCallbacks = $callbacks;
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setAfterCallbacks(array $callbacks): void
    {
        $this->afterCallbacks = $callbacks;
    }

    /**
     * @param string[] $excludedFields
     */
    public function addExcludedFields(array $excludedFields): void
    {
        /** @var string[] excludedFields */
        $this->excludedFields = array_merge($excludedFields, $this->excludedFields);
    }

    public function withRequest(RequestInterface $request): self
    {
        $self = clone $this;
        $self->request = $request;

        return $self;
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

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, Result>
     */
    public function getResult(): array
    {
        /** @var \DateTimeInterface $startedAt */
        $startedAt = $this->startedAt;

        if (null === $this->result) {
            $this->assert();
            $startedAt = $startedAt->format('Y-m-d H:i:s');
            $this->log("[{$startedAt}] {$this->result}");
        }

        /** @var Result $result */
        $result = $this->result;

        return [
            $this->getName() => $result,
        ];
    }

    /**
     * @inheritDoc
     */
    public function launch(): void
    {
        if (null === $this->requester) {
            throw new \RuntimeException("No requester configured for test '{$this->name}'.");
        }
        foreach ($this->beforeCallbacks as $callback) {
            ($callback)();
        }
        $this->startedAt = Carbon::now();
        $body = $this->request->getBody();
        if ('' !== (string) $body) {
            $body = ", Body: {$body}";
        }
        $this->logger->log(
            LogLevel::DEBUG,
            "sent request, URI: {$this->request->getUri()}{$body}"
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

    public function getStatus(): string
    {
        $status = self::STATUS_NOT_LAUNCHED;

        if (null !== $this->startedAt) {
            $status = self::STATUS_LAUNCHED;
        }
        if (null === $this->result) {
            return $status;
        }
        if (self::STATUS_LAUNCHED === $status && $this->result->hasSucceeded()) {
            return self::STATUS_SUCCESS;
        }

        return self::STATUS_FAILED;
    }

    private function assert(): void
    {
        if (self::STATUS_NOT_LAUNCHED === $this->getStatus()) {
            throw new \RuntimeException("Test {$this->getName()} was not launched.");
        }
        /** @var Requester $requester */
        $requester = $this->requester;
        try {
            $response = $requester->getResponse($this->id);
            $this->logger->log(LogLevel::DEBUG, 'received response: ' . $response->getBody());
            Assert::response(
                $this->expectedResponse,
                $response,
                $this->excludedFields
            );
        } catch (ExpectationFailedException $exception) {
            $diff = $exception->getComparisonFailure();
            $message = null !== $diff ? 'Assertion field: ' . $diff->getDiff() : $exception->getMessage();
            $this->result = Result::failed(
                $this->name . ' => ' . $message
            );

            return;
        }

        $this->result = Result::success("{$this->name} => Succeeded.");
    }

    private function log(string $msg): void
    {
        $this->logger->log(
            null !== $this->result && $this->result->hasSucceeded() ? LogLevel::INFO : LogLevel::ERROR,
            $msg
        );
    }
}
