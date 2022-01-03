<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Loader;

interface DefinitionLoader
{
    public static function getFormat(): string;

    /**
     * @throws DefinitionLoadingException
     */
    public function load(string $filePath): object;
}
