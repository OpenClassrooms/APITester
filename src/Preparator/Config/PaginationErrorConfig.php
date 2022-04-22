<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

use APITester\Preparator\Config\PaginationError\RangeConfig;

final class PaginationErrorConfig extends PreparatorConfig
{
    /**
     * @var RangeConfig[]
     */
    public array $range = [];
}
