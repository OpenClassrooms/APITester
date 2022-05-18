<?php

declare(strict_types=1);

namespace APITester\Util;

interface Filterable
{
    /**
     * @param mixed $value
     */
    public function has(string $prop, $value, string $operator = '='): bool;
}
