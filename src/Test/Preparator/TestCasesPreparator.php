<?php

declare(strict_types=1);

namespace OpenAPITesting\Test\Preparator;

use cebe\openapi\spec\OpenApi;

interface TestCasesPreparator
{
    /**
     * @return array<\OpenAPITesting\Test\TestCase>
     */
    public function __invoke(OpenApi $openApi): array;

    public function getName(): string;
}
