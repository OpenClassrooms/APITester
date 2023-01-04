<?php

declare(strict_types=1);

namespace APITester\Util;

final class Random
{
    public static function id(?string $prefix = null): string
    {
        try {
            return $prefix . bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not generate random id', 0, $e);
        }
    }
}
