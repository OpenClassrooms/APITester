<?php

declare(strict_types=1);

namespace APITester\Definition;

use cebe\openapi\spec\OpenApi;

final class OpenApiSpecification implements ApiSpecification
{
    public function __construct(
        private OpenApi $document
    ) {
    }

    public function getDocument(): OpenApi
    {
        return $this->document;
    }
}
