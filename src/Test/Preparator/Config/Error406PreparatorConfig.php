<?php

declare(strict_types=1);

namespace APITester\Test\Preparator\Config;

use APITester\Util\Mime;

final class Error406PreparatorConfig extends PreparatorConfig
{
    /**
     * @var string[]
     */
    public array $mediaTypes = Mime::TYPES;

    public int $casesCount = 3;
}
