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

    public const STATUS_FAILED = 'failed';

    public const STATUS_LAUNCHED = 'launched';

    public const STATUS_NOT_LAUNCHED = 'not launched';

    public const STATUS_SUCCESS = 'success';

    private RequestInterface $request;

    private ResponseInterface $response;

    private ?string $title;

    /**
     * @var string[]
     */
    private array $groups;

    private ?Error $error = null;

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
     * @param string[] $groups
     */
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        array $groups = [],
        ?string $title = null
    ) {
        $this->groups = $groups;
        $this->request = $request;
        $this->response = $response;
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title ?? 'test';
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        if (null === $this->error) {
            return [];
        }

        return [
            $this->getTitle() => $this->error,
        ];
    }

    /**
     * @inheritDoc
     */
    public function launch(Requester $requester, ?LoggerInterface $logger = null): void
    {
        $logger ??= new NullLogger();
        $this->startedAt = Carbon::now();
        $response = $requester->request($this->getRequest());
        $this->error = $this->assert($this->getExpectedResponse(), $response);
        $this->finishedAt = Carbon::now();
        $level = null === $this->error ? LogLevel::INFO : LogLevel::ERROR;
        $logger->log(
            $level,
            "[{$this->startedAt->format('Y-m-d H:i:s')}] {$this->getTitle()} -> {$this->getStatus()}"
        );
        $logger->log($level, (string) $this->error);
    }

    public function getExpectedResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getStatus(): string
    {
        $status = self::STATUS_NOT_LAUNCHED;

        if (null !== $this->startedAt) {
            $status = self::STATUS_LAUNCHED;
        }
        if (null === $this->error && self::STATUS_LAUNCHED === $status) {
            return self::STATUS_FAILED;
        }
        if (null !== $this->error && self::STATUS_LAUNCHED === $status) {
            return self::STATUS_FAILED;
        }

        return $status;
    }

    private function assert(ResponseInterface $expected, ResponseInterface $actual): ?Error
    {
        try {
            Assert::assertObjectsEqual($expected, $actual, $this->excludedFields);
        } catch (ExpectationFailedException $exception) {
            /** @psalm-suppress InternalMethod */
            $diff = $exception->getComparisonFailure();
            $message = null !== $diff ? 'Assertion field: ' . $diff->getDiff() : $exception->getMessage();

            return new Error(str_replace(['\\', '"stream":'], ['', '"body":'], $message));
        }

        return null;
    }
}
