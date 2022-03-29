<?php

declare(strict_types=1);

namespace APITester\Tests\Fixtures;

final class FixturesLocation
{
    public const FIXTURE_OPERATION_TEST_SUITE_1 = __DIR__ . '/Examples/operation-test-suite-1.yml';

    public const OPEN_API_INVALID_FORMAT_FILE = __DIR__ . '/OpenAPI/InvalidFormat.json';

    public const OPEN_API_INVALID_OPEN_API_FORMAT_FILE = __DIR__ . '/OpenAPI/InvalidOpenAPIFormat.json';

    public const OPEN_API_PETSTORE_YAML = __DIR__ . '/OpenAPI/petstore.yaml';

    public const OPEN_API_OC_YAML = __DIR__ . '/OpenAPI/petstore.yaml';

    public const CONFIG_OPENAPI = __DIR__ . '/Config/api-tester.yaml';
}
