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

    public static function getFullPath(string $path): string
    {
        $dir = \dirname(__DIR__);

        while (!in_array('vendor', (array) \scandir($dir), true)) {
            if ($dir === \dirname($dir)) {
                return $dir . '/' . trim($path, '/');
            }
            $dir = \dirname($dir);
        }

        return $dir . '/' . trim($path, '/');
    }
}
