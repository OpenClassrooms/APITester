<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Loader;

class OpenApiLoader implements Loader
{
    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException
     */
    public function load($data): OpenApi
    {
        $data = (array) $data;

        return new OpenApi($data);
    }
}
