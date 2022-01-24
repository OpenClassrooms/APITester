<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\Examples;

final class Request
{
    private Operation $operation;

    private string $mediaType;

    private Schema $body;

    private bool $required = true;

    private Examples $examples;

    public function __construct(string $mediaType, Schema $body)
    {
        $this->body = $body;
        $this->mediaType = $mediaType;
        $this->examples = new Examples();
    }

    public static function create(string $mediaType, Schema $body): self
    {
        return new self($mediaType, $body);
    }

    public function getBody(): Schema
    {
        return $this->body;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required = true): Request
    {
        $this->required = $required;

        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getExamples(): Examples
    {
        return $this->examples;
    }

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function setOperation(Operation $operation): self
    {
        $this->operation = $operation;

        return $this;
    }
}
