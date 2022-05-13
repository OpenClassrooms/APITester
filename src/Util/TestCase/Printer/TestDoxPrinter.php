<?php

declare(strict_types=1);

namespace APITester\Util\TestCase\Printer;

use PHPUnit\Framework\Test;
use PHPUnit\Util\TestDox\CliTestDoxPrinter;
use Throwable;

final class TestDoxPrinter extends CliTestDoxPrinter
{
    protected function formatStacktrace(Throwable $t): string
    {
        return '';
    }

    protected function formatTestName(Test $test): string
    {
        return method_exists($test, 'getName') ? $test->getName() : '';
    }
}
