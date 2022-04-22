<?php

declare(strict_types=1);

namespace APITester\Tests\Fixtures;

final class FixturesLocation
{
    public const OPEN_API_INVALID_FORMAT_FILE = __DIR__ . '/OpenAPI/InvalidFormat.json';

    public const OPEN_API_INVALID_OPEN_API_FORMAT_FILE = __DIR__ . '/OpenAPI/InvalidOpenAPIFormat.json';

    public const OPEN_API_PETSTORE_YAML = __DIR__ . '/OpenAPI/petstore.yaml';

    public const OPEN_API_OC_YAML = __DIR__ . '/OpenAPI/petstore.yaml';

    public const CONFIG_OPENAPI = __DIR__ . '/Config/api-tester.yaml';

    public const CONFIG_EXAMPLES_EXTENSION = __DIR__ . '/Examples/petstore/examples.new.yml';
}
