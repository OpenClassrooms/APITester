<?php

declare(strict_types=1);

namespace APITester\Util\TestCase\Printer;

use PHPUnit\Framework\TestFailure;
use PHPUnit\TextUI\DefaultResultPrinter;

final class DefaultPrinter extends DefaultResultPrinter
{
    protected function printDefectTrace(TestFailure $defect): void
    {
    }

    protected function printDefectHeader(TestFailure $defect, int $count): void
    {
        $this->write(
            sprintf(
                "\n%d) %s\n",
                $count,
                str_replace('ApiTestCase::', '', $defect->getTestName())
            )
        );
    }
}
