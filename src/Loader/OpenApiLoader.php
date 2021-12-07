<?php

declare(strict_types=1);

namespace OpenAPITesting\Loader;

use cebe\openapi\spec\OpenApi;

final class OpenApiLoader
{
    /**
     * @param array<int|string, mixed> $data
     *
     * @throws \cebe\openapi\exceptions\TypeErrorException
     */
    public function __invoke(array $data): OpenApi
    {
        return new OpenApi($data);
    }
}
