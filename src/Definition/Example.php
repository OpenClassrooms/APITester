<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

final class Example
{
    private string $name;

    /**
     * @var array<array-key, mixed>|object
     */
    private $value;

    /**
     * @param array<array-key, mixed>|object $value
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
     * @return array<array-key, mixed>|object
     */
    public function getValue()
    {
        return $this->value;
    }
}
