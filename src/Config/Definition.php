<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class Definition
{
    private string $path;

    private string $format;

    public function __construct(string $path, string $format)
    {
        if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
            $path = PROJECT_DIR . '/' . trim($path, '/');
        }
        $this->path = $path;
        $this->format = $format;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
