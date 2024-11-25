<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

abstract class Error400PreparatorConfig extends PreparatorConfig
{
    public bool $excludeOpenApiEndpoints = false;
}
