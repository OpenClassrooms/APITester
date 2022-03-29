<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

use APITester\Preparator\Config\PaginationError\Range;

final class PaginationError extends PreparatorConfig
{
    /**
     * @var Range[]
     */
    public array $range = [];
}
