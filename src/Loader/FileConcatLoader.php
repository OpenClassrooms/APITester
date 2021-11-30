<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use OpenAPITesting\Loader;

final class FileConcatLoader implements Loader
{
    private string $separator;

    public function __construct(string $separator = "\n")
    {
        $this->separator = $separator;
    }

    public function load($data): string
    {
        $files = (array) $data;
        $content = [];
        foreach ($files as $file) {
            $content[] = file_get_contents($file);
        }

        return implode($this->separator, $content);
    }
}
