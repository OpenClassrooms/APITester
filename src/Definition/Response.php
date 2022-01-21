<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\Examples;
use OpenAPITesting\Definition\Collection\Headers;

final class Response
{
    private string $mediaType = 'application/json';

    private int $statusCode = 200;

    private Headers $headers;

    private ?Schema $body = null;

    private Examples $examples;

    private string $description = '';

    public function __construct()
    {
        $this->headers = new Headers();
        $this->examples = new Examples();
    }

    public static function create(): self
    {
        return new self();
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): Response
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): Headers
    {
        return $this->headers;
    }

    public function setHeaders(Headers $headers): Response
    {
        $this->headers = $headers;

        return $this;
    }

    public function getBody(): ?Schema
    {
        return $this->body;
    }

    public function setBody(Schema $body): Response
    {
        $this->body = $body;

        return $this;
    }

    public function getExamples(): Examples
    {
        return $this->examples;
    }

    public function setExamples(Examples $examples): Response
    {
        $this->examples = $examples;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
