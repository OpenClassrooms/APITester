<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\ParameterExamples;

final class Parameter
{
    public const TYPE_QUERY = 'query';

    public const TYPE_PATH = 'path';

    public const TYPE_HEADER = 'header';

    private Operation $parent;

    private string $name;

    private bool $required;

    private ?Schema $schema;

    private ParameterExamples $examples;

    public function __construct(string $name, bool $required = false, ?Schema $schema = null)
    {
        $this->name = $name;
        $this->examples = new ParameterExamples();
        $this->required = $required;
        $this->schema = $schema;
    }

    public static function create(string $name, bool $required = false): self
    {
        return new self($name, $required);
    }

    public function getName(): string
    {
        return $this->name;
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

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function getType(): ?string
    {
        $schema = $this->getSchema();

        if (null !== $schema && null !== $schema->type) {
            return $schema->type;
        }

        return null;
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
}
