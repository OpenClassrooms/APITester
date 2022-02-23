<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Collection\ResponseExamples;

final class Response
{
    private Operation $parent;

    private ?string $mediaType = null;

    private int $statusCode;

    private Parameters $headers;

    private ?Schema $body = null;

    private ResponseExamples $examples;

    private string $description = '';

    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
        $this->headers = new Parameters();
        $this->examples = new ResponseExamples();
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

    public function getBody(): ?Schema
    {
        return $this->body;
    }

    public function setBody(?Schema $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getExamples(): ResponseExamples
    {
        return $this->examples;
    }

    public function setExamples(ResponseExamples $examples): self
    {
        foreach ($examples as $example) {
            $example->setParent($this);
        }
        $this->examples = $examples;

        return $this;
    }

    public function addExample(ResponseExample $example): self
    {
        $example->setParent($this);
        $this->examples->add($example);

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
