<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

final class DirectoryPathLoader
{
    /**
     * @return array<int, string>
     */
    public function load(string $path): array
    {
        $files = [];
        $directory = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($directory);
        /** @var \SplFileInfo $info */
        foreach ($iterator as $info) {
            $files[] = $info->getPathname();
        }

        return $files;
    }
}
