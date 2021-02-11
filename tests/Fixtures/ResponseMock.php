<?php

namespace OpenAPITesting\Tests\Fixtures;

use Symfony\Contracts\HttpClient\ResponseInterface;

class ResponseMock implements ResponseInterface
{
    private string $body;

    private array $headers;

    private int $statusCode;

    public function __construct(int $statusCode = 200, array $headers = [], string $body = '')
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(bool $throw = true): array
    {
        return $this->headers;
    }

    public function getContent(bool $throw = true): string
    {
        return $this->body;
    }

    public function toArray(bool $throw = true): array
    {
        // TODO: Implement toArray() method.
    }

    public function cancel(): void
    {
        // TODO: Implement cancel() method.
    }

    public function getInfo(string $type = null)
    {
        // TODO: Implement getInfo() method.
    }
}