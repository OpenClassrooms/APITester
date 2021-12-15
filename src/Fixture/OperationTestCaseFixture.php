<?php

declare(strict_types=1);

namespace OpenAPITesting\Fixture;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class OperationTestCaseFixture
{
    /**
     * @var array{'method'?: string, 'path'?: string, 'headers'?: array<string, string|string[]>, 'body'?: string}
     */
    public array $request = [];

    /**
     * @var array{'statusCode'?: int, 'headers'?: array<string, string|string[]>, 'body'?: string}
     */
    public array $response = [];

    private ?string $description = null;

    private ?string $operationId = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getExpectedResponse(): ResponseInterface
    {
        return new Response(
            $this->response['statusCode'] ?? 0,
            $this->response['headers'] ?? [],
            $this->response['body'] ?? ''
        );
    }

    public function getOperationId(): ?string
    {
        return $this->operationId;
    }

    public function setOperationId(string $operationId): void
    {
        $this->operationId = $operationId;
    }

    public function getRequestBody(): ?string
    {
        return $this->request['body'] ?? null;
    }

    /**
     * @return array<string, string|string[]>
     */
    public function getRequestHeaders(): array
    {
        return $this->request['headers'] ?? [];
    }

    public function getMethod(): string
    {
        return $this->request['method'] ?? 'get';
    }

    public function getPath(): string
    {
        return $this->request['path'] ?? '';
    }
}
