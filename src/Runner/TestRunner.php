<?php

declare(strict_types=1);

namespace APITester\Runner;

use APITester\Config;

interface TestRunner
{
    /**
     * @param array<string, mixed> $runnerOptions
     */
    public function createRunnerFile(
        Config\Suite $suiteConfig,
        string $configPath,
        string $suiteName,
        array $runnerOptions
    ): string;

    public function cleanupRunnerFile(string $testFile): void;

    /**
     * @param array<string, mixed>   $passThroughOptions Options forwarded to the test runner CLI
     * @param callable(string): void $writeOutput
     */
    public function run(
        array $passThroughOptions,
        Config\Suite $suiteConfig,
        callable $writeOutput,
        string $testFile
    ): int;
}
