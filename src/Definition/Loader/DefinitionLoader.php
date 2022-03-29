<?php

declare(strict_types=1);

namespace APITester\Definition\Loader;

use APITester\Definition\Api;
use APITester\Definition\Loader\Exception\DefinitionLoadingException;

interface DefinitionLoader
{
    public static function getFormat(): string;

    /**
     * @throws DefinitionLoadingException
     */
    public function load(string $filePath): Api;
}
