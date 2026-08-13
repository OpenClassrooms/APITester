<?php

declare(strict_types=1);

namespace APITester\Test\Preparator\Config;

final class Error406PreparatorConfig extends PreparatorConfig
{
    /**
     * @var string[]
     */
    public array $mediaTypes = ['application/vnd.api-tester.unsupported'];

    public int $casesCount = 1;
}
