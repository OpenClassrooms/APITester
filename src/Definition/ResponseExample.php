<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

final class ResponseExample
{
    private Response $parent;

    private string $name;

    /**
     * @var array<array-key, mixed>|null
     */
    private ?array $value;

    /**
     * @param array<array-key, mixed>|null $value
     */
    public function __construct(string $name, ?array $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function getValue(): ?array
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
