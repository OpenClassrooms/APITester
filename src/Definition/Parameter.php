<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\ParameterExamples;

final class Parameter
{
    private Operation $parent;

    private string $name;

    private ?Schema $schema;

    private ParameterExamples $examples;

    public function __construct(string $name, ?Schema $schema = null)
    {
        $this->name = $name;
        $this->examples = new ParameterExamples();
        $this->schema = $schema;
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

    public function getExamples(): ParameterExamples
    {
        return $this->examples;
    }

    public function addExample(ParameterExample $example): self
    {
        $example->setParent($this);
        $this->examples->add($example);

        return $this;
    }

    public function getParent(): Operation
    {
        return $this->parent;
    }

    public function setParent(Operation $operation): self
    {
        $this->parent = $operation;

        return $this;
    }
}
