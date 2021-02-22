<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use OpenAPITesting\Loader;

class DirectoryPathLoader implements Loader
{
    public function load($data): array
    {
        $directories = (array) $data;
        $files = [];
        foreach ($directories as $path) {
            $directory = new \RecursiveDirectoryIterator($path);
            $iterator = new \RecursiveIteratorIterator($directory);
            foreach ($iterator as $info) {
                $files[] = $info->getPathname();
            }
        }

        return $files;
    }
}
