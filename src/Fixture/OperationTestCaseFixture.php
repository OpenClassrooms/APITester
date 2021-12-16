<?php

declare(strict_types=1);

namespace OpenAPITesting\Fixture;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class OperationTestCaseFixture
{
    private RequestInterface $request;

    private ResponseInterface $response;

    private ?string $description;

    private string $operationId;

    public function __construct(
        string $operationId,
        RequestInterface $request,
        ResponseInterface $response,
        ?string $description = null
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->operationId = $operationId;
        $this->description = $description;
    }

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
        return $this->response;
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function setOperationId(string $operationId): void
    {
        $this->operationId = $operationId;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }


}
