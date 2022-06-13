<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

final class Error403PreparatorConfig extends PreparatorConfig
{
    /**
     * @var string[]
     */
    public array $excludedTokens = [];
}
