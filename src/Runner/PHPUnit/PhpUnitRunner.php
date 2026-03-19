<?php

declare(strict_types=1);

namespace APITester\Runner\PHPUnit;

final class PhpUnitRunner extends AbstractPhpUnitRunner
{
    protected function getBinaryName(): string
    {
        return 'phpunit';
    }

    protected function getRunnerSpecificArgs(string $testFile): array
    {
        return [];
    }
}
