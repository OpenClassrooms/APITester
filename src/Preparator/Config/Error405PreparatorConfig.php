<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

final class Error405PreparatorConfig extends PreparatorConfig
{
    /**
     * @var string[]
     */
    public array $methods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'OPTIONS',
        'TRACE',
        'CONNECT',
    ];
}
