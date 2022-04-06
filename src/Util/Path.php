<?php

declare(strict_types=1);

namespace APITester\Util;

use Composer\Autoload\ClassLoader;

final class Path
{
    public static function getBasePath(): string
    {
        $reflection = new \ReflectionClass(ClassLoader::class);

        return \dirname((string) $reflection->getFileName(), 3);
    }
}
