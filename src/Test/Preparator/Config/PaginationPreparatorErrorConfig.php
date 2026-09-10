<?php

declare(strict_types=1);

namespace APITester\Test\Preparator\Config;

use APITester\Test\Preparator\Config\PaginationError\RangeConfig;

final class PaginationPreparatorErrorConfig extends PreparatorConfig
{
    /**
     * @var RangeConfig[]
     */
    public array $range = [];
}
