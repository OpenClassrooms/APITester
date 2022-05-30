<?php

declare(strict_types=1);

namespace APITester\Config;

use APITester\Util\Path;

final class Definition
{
    private string $format;

    private string $path;

    public function __construct(string $path, string $format)
    {
        $path = trim($path, '/');
        $fullPath = $path;
        if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
            $fullPath = Path::getBasePath() . '/' . $path;
        }
        $this->path = $fullPath;
        $this->format = $format;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}