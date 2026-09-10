<?php

declare(strict_types=1);

namespace APITester\Util\Traits;

trait FilterableTrait
{
    public function has(string $prop, $value, string $operator = '='): bool
    {
        $self = collect([$this]);

        if (str_contains($prop, '*')) {
            $operator = 'contains';
        }

        return $self
            ->where($prop, $operator, $value)
            ->first() !== null
        ;
    }
}
