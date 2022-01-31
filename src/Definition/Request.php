<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\RequestExamples;

final class Request
{
    private Operation $parent;

    private string $mediaType;

    private Schema $body;

    private bool $required = true;

    private RequestExamples $examples;

    public function __construct(string $mediaType, Schema $body)
    {
        $this->body = $body;
        $this->mediaType = $mediaType;
        $this->examples = new RequestExamples();
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

    public function setRequired(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getExamples(): RequestExamples
    {
        return $this->examples;
    }

    public function addExample(RequestExample $example): self
    {
        $example->setParent($this);
        $this->examples->add($example);

        return $this;
    }

    public function getParent(): Operation
    {
        return $this->parent;
    }

    public function setParent(Operation $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
