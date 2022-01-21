<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\Examples;
use OpenAPITesting\Definition\Collection\Headers;

final class Response
{
    private string $mediaType;

    private int $status;

    private Headers $headers;

    private ?Schema $body;

    private Examples $examples;

    private string $description;

    public function __construct(
        string $mediaType,
        int $status,
        Headers $headers,
        ?Schema $body,
        string $description,
        ?Examples $examples = null
    ) {
        $this->mediaType = $mediaType;
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
        $this->description = $description;
        $this->examples = $examples ?? new Examples();
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getHeaders(): Headers
    {
        return $this->headers;
    }

    public function getBody(): ?Schema
    {
        return $this->body;
    }

    public function getExamples(): Examples
    {
        return $this->examples;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
