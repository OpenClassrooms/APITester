<?php

declare(strict_types=1);

namespace APITester\Definition;

final class RequestExample
{
    private Request $parent;

    private string $name;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param mixed $value
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getParent(): Request
    {
        return $this->parent;
    }

    public function setParent(Request $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
