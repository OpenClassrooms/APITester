<?php

declare(strict_types=1);

namespace APITester\Definition;

final class Tag
{
    public function __construct(private readonly string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
