<?php

declare(strict_types=1);

namespace APITester\Definition;

use APITester\Definition\Collection\Parameters;
use cebe\openapi\spec\Schema;

final class Response
{
    private Operation $parent;

    private ?string $mediaType = null;

    private int $statusCode;

    private Parameters $headers;

    private ?Schema $body = null;

    private string $description = '';

    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
        $this->headers = new Parameters();
    }

    public static function create(int $statusCode): self
    {
        return new self($statusCode);
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): self
    {
        $this->mediaType = $mediaType;

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

    public function getHeaders(): Parameters
    {
        return $this->headers;
    }

    public function setHeaders(Parameters $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function addHeader(Parameter $header): self
    {
        $this->headers->add($header);

        return $this;
    }

    public function getBody(): ?Schema
    {
        return $this->body;
    }

    public function setBody(?Schema $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getParent(): Operation
    {
        return $this->parent;
    }

    public function setParent(Operation $parent): void
    {
        $this->parent = $parent;
    }
}
