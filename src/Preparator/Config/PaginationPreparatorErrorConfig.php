<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

use APITester\Preparator\Config\PaginationError\RangeConfig;

final class PaginationPreparatorErrorConfig extends PreparatorConfig
{
    /**
     * @var RangeConfig[]
     */
    public array $range = [];
}
