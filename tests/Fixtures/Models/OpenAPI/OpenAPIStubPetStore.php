<?php

namespace OpenAPITesting\Tests\Fixtures\Models\OpenAPI;

use cebe\openapi\spec\OpenApi;

class OpenAPIStubPetStore extends OpenApi
{
    public const TITLE = 'Swagger Petstore - OpenAPI 3.0';

    public const VERSION = '1.0.5';

    public function __construct(array $data = [])
    {
        parent::__construct(['info' => ['title' => self::TITLE, 'version' => self::VERSION]]);
    }
}