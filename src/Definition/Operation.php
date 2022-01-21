<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Collection\Requests;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Collection\Tags;

final class Operation
{
    private string $id;

    private string $path;

    private string $method;

    private Parameters $parameters;

    private Requests $requests;

    private Responses $responses;

    private string $summary = '';

    private string $description = '';

    private Tags $tags;

    public function __construct(
        string $id,
        string $path
    ) {
        $this->id = $id;
        $this->path = $path;
        $this->method = 'GET';
        $this->parameters = new Parameters();
        $this->requests = new Requests();
        $this->responses = new Responses();
        $this->tags = new Tags();
    }

    public static function create(string $id, string $path): self
    {
        return new static($id, $path);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPath(array $params = [], array $query = []): string
    {
        $names = array_map(static fn (string $x) => "{{$x}}", array_keys($params));
        $path = str_replace($names, array_values($params), $this->path);

        return $path . '?' . http_build_query($query);
    }

    public function getMethod(): string
    {
        return mb_strtoupper($this->method);
    }

    public function setMethod(string $method): Operation
    {
        $this->method = $method;

        return $this;
    }

    public function getRequests(): Requests
    {
        return $this->requests;
    }

    public function setRequests(Requests $requests): self
    {
        $this->requests = $requests;

        return $this;
    }

    public function getRequest(string $mediaType): Request
    {
        return $this->requests[$mediaType];
    }

    public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    public function setParameters(Parameters $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getResponses(): Responses
    {
        return $this->responses;
    }

    public function setResponses(Responses $responses): self
    {
        $this->responses = $responses;

        return $this;
    }

    public function addResponse(Response $response): self
    {
        $this->responses->add($response);

        return $this;
    }

    public function getTags(): Tags
    {
        return $this->tags;
    }

    public function setTags(Tags $tags): Operation
    {
        $this->tags = $tags;

        return $this;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): Operation
    {
        $this->summary = $summary;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Operation
    {
        $this->description = $description;

        return $this;
    }

    public function getSecurity(): Security
    {
        return new Security();
    }
}
