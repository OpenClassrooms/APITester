<?php

namespace OpenAPITesting\Models\Fixture;

use Nyholm\Psr7\Response;

class OperationTestCaseFixture
{
    protected string $description;

    protected string $id;

    protected array $request = [];

    /**
     * @var Response[]
     */
    protected array $responses;

    protected string $title;

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setRequest(array $request): void
    {
        $this->request = $request;
    }

    /**
     * @return Response[]
     */
    public function getExpectedResponses(): array
    {
        return $this->responses;
    }

    public function setResponse(array $responseData): void
    {
        $this->responses[] = new Response($responseData['statusCode'] ?? null, $responseData['headers'] ?? [], $responseData['body'] ?? null);
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getRequestPathParameters(): array
    {
        if (array_key_exists('parameters', $this->request)) {
            if (array_key_exists('path', $this->request['parameters'])) {
                return $this->request['parameters']['path'];
            }
        }

        return [];
    }

    public function getRequestQueryParameters(): array
    {
        if (array_key_exists('parameters', $this->request)) {
            if (array_key_exists('query', $this->request['parameters'])) {
                return $this->request['parameters']['query'];
            }
        }

        return [];
    }

    public function getRequestBody(): ?string
    {
        if (array_key_exists('body', $this->request)) {
            return $this->request['body'];
        }

        return null;
    }

    public function getRequestHeaders(): array
    {
        if (array_key_exists('headers', $this->request)) {
            return $this->request['headers'];
        }

        return [];
    }
}