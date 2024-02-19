<?php

declare(strict_types=1);

namespace APITester\Definition\Loader;

use APITester\Definition\Api;
use APITester\Definition\Loader\Exception\DefinitionLoadingException;
use Psr\Log\LoggerInterface;

interface DefinitionLoader
{
    public static function getFormat(): string;

    public function setLogger(LoggerInterface $logger): void;

    /**
     * @throws DefinitionLoadingException
     */
    public function load(string $filePath): Api;
}
