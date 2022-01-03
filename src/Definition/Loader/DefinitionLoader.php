<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Loader;

interface DefinitionLoader
{
    /**
     * @throws DefinitionLoadingException
     */
    public function load(string $filePath): object;

    public function getFormat(): string;
}
