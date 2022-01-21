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

    private string $summary;

    private string $description;

    private Tags $tags;

    public function __construct(
        string $id,
        string $summary,
        string $description,
        string $path,
        string $method,
        Parameters $parameters,
        Requests $requests,
        Responses $responses,
        Tags $tags
    ) {
        $this->id = $id;
        $this->path = $path;
        $this->method = $method;
        $this->parameters = $parameters;
        $this->requests = $requests;
        $this->responses = $responses;
        $this->summary = $summary;
        $this->description = $description;
        $this->tags = $tags;
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

    public function getRequests(): Requests
    {
        return $this->requests;
    }

    public function getRequest(string $mediaType): Request
    {
        return $this->requests[$mediaType];
    }

    public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    public function getResponses(array $filters = []): Responses
    {
        return $this->responses;
    }

    public function getTags(): Tags
    {
        return $this->tags;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSecurity(): Security
    {
        return new Security();
    }
}
