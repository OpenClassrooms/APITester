<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Assert;
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

    /**
     * @var string[]
     */
    private array $groups;

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

    private ?\Closure $beforeCallback = null;

    private ?\Closure $afterCallback = null;

    /**
     * @param string[] $groups
     */
    public function __construct(
        string $name,
        RequestInterface $request,
        ResponseInterface $expectedResponse,
        array $groups = []
    ) {
        $this->groups = $groups;
        $this->request = $request;
        $this->expectedResponse = $expectedResponse;
        $this->name = $name;
        $this->logger = new NullLogger();
        $this->id = uniqid('testcase_', false);
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

        /** @var \OpenAPITesting\Test\Result $result */
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
        if (null !== $this->beforeCallback) {
            ($this->beforeCallback)();
        }
        $this->startedAt = Carbon::now();
        $this->requester->request($this->request, $this->id);
        $this->finishedAt = Carbon::now();
        if (null !== $this->afterCallback) {
            ($this->afterCallback)();
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

    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
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

    public function setBeforeCallback(?\Closure $beforeCallback): void
    {
        $this->beforeCallback = $beforeCallback;
    }

    public function setAfterCallback(?\Closure $afterCallback): void
    {
        $this->afterCallback = $afterCallback;
    }

    private function assert(): void
    {
        if (self::STATUS_NOT_LAUNCHED === $this->getStatus()) {
            throw new \RuntimeException("Test {$this->getName()} was not launched.");
        }
        /** @var Requester $requester */
        $requester = $this->requester;
        try {
            Assert::objectsEqual(
                $this->expectedResponse,
                $requester->getResponse($this->id),
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
