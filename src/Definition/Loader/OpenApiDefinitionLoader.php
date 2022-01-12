<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Loader;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException;

final class OpenApiDefinitionLoader implements DefinitionLoader
{
    public const FORMAT_JSON = 'json';

    public const FORMAT_YAML = 'yaml';

    public const FORMATS = [self::FORMAT_JSON, self::FORMAT_YAML];

    public function load(string $filePath, string $format = self::FORMAT_YAML): OpenApi
    {
        if (!\in_array($format, self::FORMATS, true)) {
            throw new \InvalidArgumentException('Invalid format ' . $format);
        }

        try {
            return Reader::readFromYamlFile($filePath);
        } catch (\Exception $e) {
            throw new DefinitionLoadingException($e);
        }
    }

    public static function getFormat(): string
    {
        return 'openapi';
    }
}
