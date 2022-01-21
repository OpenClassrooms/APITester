<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\Examples;

final class Request
{
    public string $mediaType;

    private ?Schema $body;

    private bool $required;

    private Examples $examples;

    public function __construct(?Schema $body, string $mediaType, bool $required = true, ?Examples $examples = null)
    {
        $this->body = $body;
        $this->mediaType = $mediaType;
        $this->required = $required;
        $this->examples = $examples ?? new Examples();
    }

    public function getBody(): ?Schema
    {
        return $this->body;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getExamples(): Examples
    {
        return $this->examples;
    }
}
