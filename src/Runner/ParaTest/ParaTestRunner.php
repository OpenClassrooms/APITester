<?php

declare(strict_types=1);

namespace APITester\Runner\ParaTest;

use APITester\Runner\PHPUnit\AbstractPhpUnitRunner;

final class ParaTestRunner extends AbstractPhpUnitRunner
{
    public function __construct(
        private readonly int $processes
    ) {
    }

    protected function getBinaryName(): string
    {
        return 'paratest';
    }

    /**
     * @return list<string>
     */
    protected function getRunnerSpecificArgs(string $testFile): array
    {
        return [
            '--runner=WrapperRunner',
            '--processes=' . $this->processes,
            '--bootstrap=' . $testFile,
        ];
    }
}
