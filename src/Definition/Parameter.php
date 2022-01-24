<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\Examples;

final class Parameter
{
    private Operation $operation;

    private string $name;

    private ?Schema $schema = null;

    private Examples $examples;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->examples = new Examples();
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }

    public function setSchema(?Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
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
