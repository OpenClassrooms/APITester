<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

final class ParameterExample
{
    private Parameter $parent;

    private string $name;

    private string $value;

    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setParent(Parameter $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): Parameter
    {
        return $this->parent;
    }
}
