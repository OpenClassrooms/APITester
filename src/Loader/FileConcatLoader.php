<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

final class FileConcatLoader
{
    /**
     * @param array<int, string> $fileNames
     */
    public function __invoke(array $fileNames, string $separator = "\n"): string
    {
        $content = [];
        foreach ($fileNames as $fileName) {
            $content[] = file_get_contents($fileName);
        }

        return implode($separator, $content);
    }
}
