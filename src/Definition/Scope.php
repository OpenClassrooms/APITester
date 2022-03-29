<?php

declare(strict_types=1);

namespace APITester\Definition;

final class Scope
{
    private string $name;

    private string $description;

    public function __construct(string $name, string $description = '')
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
