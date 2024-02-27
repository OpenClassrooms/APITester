<?php

declare(strict_types=1);

namespace APITester\Definition\Loader;

use APITester\Definition\Api;
use APITester\Definition\Loader\Exception\DefinitionLoadingException;
use Psr\Log\LoggerInterface;

interface DefinitionLoader
{
    public const FORMAT_JSON = 'json';

    public const FORMAT_YAML = 'yaml';

    public const FORMATS = [self::FORMAT_JSON, self::FORMAT_YAML];

    public static function getFormat(): string;

    public function setLogger(LoggerInterface $logger): void;

    /**
     * @param array<string, string[]> $filters
     *
     * @throws DefinitionLoadingException
     */
    public function load(string $filePath, string $format = self::FORMAT_YAML, array $filters = []): Api;
}
