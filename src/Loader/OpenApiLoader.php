<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;

final class OpenApiLoader
{
    public const FORMAT_JSON = 'json';

    public const FORMAT_YAML = 'yaml';

    public const FORMATS = [self::FORMAT_JSON, self::FORMAT_YAML];

    /**
     * @param self::FORMAT_* $format
     *
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
     * @throws \cebe\openapi\exceptions\IOException
     * @throws \cebe\openapi\exceptions\TypeErrorException
     * @throws \InvalidArgumentException
     */
    public function __invoke(string $file, string $format = self::FORMAT_YAML): OpenApi
    {
        if (! \in_array($format, self::FORMATS, true)) {
            throw new \InvalidArgumentException('Invalid format ' . $format);
        }

        return Reader::readFromYamlFile(FixturesLocation::OPEN_API_PETSTORE_YAML);
    }
}
