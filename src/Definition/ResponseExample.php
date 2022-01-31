<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

final class ResponseExample
{
    private Response $parent;

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

    public function getParent(): Response
    {
        return $this->parent;
    }

    public function setParent(Response $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
