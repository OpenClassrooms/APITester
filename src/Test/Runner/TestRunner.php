<?php

declare(strict_types=1);

namespace APITester\Test\Runner;

interface TestRunner
{
    /**
     * @param array<string, mixed> $runnerOptions
     */
    public function createRunnerFile(
        \APITester\Runtime\Config\Entity\Suite $suiteConfig,
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
        \APITester\Runtime\Config\Entity\Suite $suiteConfig,
        callable $writeOutput,
        string $testFile
    ): int;
}
