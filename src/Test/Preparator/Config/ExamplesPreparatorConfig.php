<?php

declare(strict_types=1);

namespace APITester\Test\Preparator\Config;

final class ExamplesPreparatorConfig extends PreparatorConfig
{
    public ?string $extensionPath = null;

    public bool $autoComplete = false;

    public bool $autoCreateWhenMissing = false;
}
