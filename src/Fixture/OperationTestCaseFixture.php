<?php

namespace OpenAPITesting\Fixture;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class OperationTestCaseFixture
{
    protected string $operationId;

    private string $description;

    private array $request = [];

    private array $response = [];

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getExpectedResponse(): ResponseInterface
    {
        return new Response(
            $this->response['statusCode'] ?? null,
            $this->response['headers'] ?? [],
            $this->response['body'] ?? null
        );
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function getRequestBody(): ?string
    {
        return $this->request['body'] ?? null;
    }

    public function getRequestHeaders(): array
    {
        return $this->request['headers'] ?? [];
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setOperationId(string $operationId): void
    {
        $this->operationId = $operationId;
    }

    public function setRequest(array $request): void
    {
        $this->request = $request;
    }

    public function setResponse(array $response): void
    {
        $this->response = $response;
    }
}
