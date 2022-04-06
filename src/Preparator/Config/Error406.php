<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

use APITester\Util\Mime;

final class Error406 extends PreparatorConfig
{
    /**
     * @var string[]
     */
    public array $mediaTypes = Mime::TYPES;

    public int $casesCount = 3;
}
