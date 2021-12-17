<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

use Carbon\Carbon;
use DateTimeInterface;
use OpenAPITesting\Requester;
use OpenAPITesting\Test;
use OpenAPITesting\Util\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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

    private RequestInterface $request;

    private ResponseInterface $response;

    private ?string $description;

    /**
     * @var string[]
     */
    private array $groups;

    private ?ExpectationFailedException $errors = null;

    private ?DateTimeInterface $startedAt = null;

    private ?DateTimeInterface $finishedAt = null;

    /**
     * @param string[] $groups
     */
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        array $groups = [],
        ?string $description = null
    ) {
        $this->groups = $groups;
        $this->request = $request;
        $this->response = $response;
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description ?? 'test';
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
        $this->startedAt = Carbon::now();
        $response = $requester->request($this->getRequest());
        $this->errors = $this->assert($this->getExpectedResponse(), $response);
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

    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    private function assert(ResponseInterface $expected, ResponseInterface $actual): ?ExpectationFailedException
    {
        try {
            Assert::assertObjectsEqual($expected, $actual);
        } catch (ExpectationFailedException $exception) {
            return $exception;
        }

        return null;
    }
}
