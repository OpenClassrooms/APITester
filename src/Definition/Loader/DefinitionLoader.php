<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Loader;

use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException;

interface DefinitionLoader
{
    public static function getFormat(): string;

    /**
     * @throws DefinitionLoadingException
     */
    public function load(string $filePath): Api;
}
