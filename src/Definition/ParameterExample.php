<?php

declare(strict_types=1);

namespace APITester\Definition;

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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getParent(): Parameter
    {
        return $this->parent;
    }

    public function setParent(Parameter $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
