<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

final class ExampleFixture
{
    private string $operationId;

    /**
     * @var array<string, string>
     */
    private array $parameters = [];

    /**
     * @var array<array-key, mixed>|null
     */
    private ?array $requestBody = null;

    private int $statusCode;

    /**
     * @var array<array-key, mixed>|null
     */
    private ?array $responseBody = null;

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function setOperationId(string $operationId): self
    {
        $this->operationId = $operationId;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return ExampleFixture
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function getRequestBody(): ?array
    {
        return $this->requestBody;
    }

    /**
     * @param array<array-key, mixed>|null $requestBody
     */
    public function setRequestBody(?array $requestBody): self
    {
        $this->requestBody = $requestBody;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    /**
     * @param array<array-key, mixed>|null $responseBody
     */
    public function setResponseBody(?array $responseBody): self
    {
        $this->responseBody = $responseBody;

        return $this;
    }
}
