<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class DefinitionConfig
{
    private string $path;

    private string $format;

    public function __construct(string $path, string $format)
    {
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
