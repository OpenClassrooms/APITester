<?php

namespace OpenAPITesting\Tests\Fixtures\Models;

use OpenAPITesting\Models\Configuration;

class ConfigurationStub1 extends Configuration
{
    public string $openAPILocation = __DIR__ . '/../OpenAPIFiles/petstore.json';
}