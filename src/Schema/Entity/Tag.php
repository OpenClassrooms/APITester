<?php

declare(strict_types=1);

namespace APITester\Schema\Entity;

final class Tag
{
    public function __construct(
        private readonly string $name
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
