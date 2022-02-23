<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator\Config;

use OpenAPITesting\Preparator\Config\PaginationError\Range;

final class PaginationError extends PreparatorConfig
{
    /**
     * @var Range[]
     */
    public array $range = [];
}
