<?php

declare(strict_types=1);

namespace APITester\Runner\PHPUnit;

use APITester\Config;
use APITester\Runner\TestRunner;
use APITester\Util\Object_;
use APITester\Util\Path;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Symfony\Component\Process\Process;

abstract class AbstractPhpUnitRunner implements TestRunner
{
    /**
     * @var array<string, string>
     */
    private array $progressFiles = [];

    /**
     * @param array<string, mixed> $runnerOptions
     */
    final public function createRunnerFile(
        Config\Suite $suiteConfig,
        string $configPath,
        string $suiteName,
        array $runnerOptions
    ): string {
        $testCaseClass = Object_::validateClass(
            ltrim($suiteConfig->getTestCaseClass(), '\\'),
            PhpUnitTestCase::class
        );

        $dir = tempnam(sys_get_temp_dir(), 'api-tester-runner-');
        if ($dir === false) {
            throw new \RuntimeException('Could not create a temporary directory for the runner file.');
        }
        if (is_file($dir) && !unlink($dir)) {
            throw new \RuntimeException('Could not prepare temporary directory for the runner file.');
        }
        if (!is_dir($dir) && !mkdir($dir)) {
            throw new \RuntimeException('Could not create a temporary directory for the runner file.');
        }
        $file = $dir . '/ApiTesterRunnerTest.php';

        $parent = '\\' . $testCaseClass;
        $configExport = var_export($configPath, true);
        $suiteExport = var_export($suiteName, true);
        $optionsExport = var_export($runnerOptions, true);
        $progressFile = tempnam(sys_get_temp_dir(), 'api-tester-progress-');
        if ($progressFile === false) {
            throw new \RuntimeException('Could not create a temporary progress file.');
        }
        $progressFileExport = var_export($progressFile, true);

        $content = <<<'PHP'
            <?php

            declare(strict_types=1);

            use APITester\Config\Loader\PlanConfigLoader;
            use APITester\Test\Plan;
            use APITester\Test\TestCase;
            use Symfony\Component\Console\Logger\ConsoleLogger;
            use Symfony\Component\Console\Output\ConsoleOutput;
            use Symfony\Component\HttpKernel\HttpKernelInterface;

            final class ApiTesterRunnerTest extends __APITESTER_PARENT__
            {
                private const CONFIG_PATH = __APITESTER_CONFIG__;
                private const SUITE_NAME = __APITESTER_SUITE__;
                private const OPTIONS = __APITESTER_OPTIONS__;
                private const PROGRESS_FILE = __APITESTER_PROGRESS_FILE__;

                /**
                 * @return iterable<string, array{0: TestCase}>
                 */
                public static function apiTestCases(): iterable
                {
                    $config = PlanConfigLoader::load(self::CONFIG_PATH);
                    $plan = new Plan();

                    $verbosity = self::OPTIONS['verbosity'] ?? 32;
                    $plan->setLogger(new ConsoleLogger(new ConsoleOutput($verbosity)));

                    $seenDataSetNames = [];
                    foreach ($plan->getTestCases($config, self::SUITE_NAME, self::OPTIONS) as $testCase) {
                        $dataSetName = $testCase->getName();
                        $seenDataSetNames[$dataSetName] = ($seenDataSetNames[$dataSetName] ?? 0) + 1;
                        if ($seenDataSetNames[$dataSetName] > 1) {
                            $dataSetName .= ' #' . $seenDataSetNames[$dataSetName];
                        }

                        yield $dataSetName => [$testCase];
                    }
                }

                /**
                 * @param array<array-key, mixed> $data
                 * @param int|string              $dataName
                 */
                public function __construct(?string $name = null, array $data = [], $dataName = '')
                {
                    if ($name !== null && $data === [] && $dataName === '') {
                        $pattern = '/^(?<method>[^ ]+) with data set "(?<dataName>.*)"$/';
                        if (preg_match($pattern, $name, $m) === 1) {
                            $name = $m['method'];
                            $dataName = $m['dataName'];
                            $data = [null];
                        }
                    }

                    parent::__construct($name, $data, $dataName);
                }

                /**
                 * @dataProvider apiTestCases
                 */
                public function testApi(TestCase $testCase): void
                {
                    self::reportRunningTestCase($testCase);

                    $kernel = null;
                    if (method_exists($this, 'getKernel')) {
                        $candidate = $this->getKernel();
                        if ($candidate instanceof HttpKernelInterface) {
                            $kernel = $candidate;
                        }
                    }

                    $testCase->test($kernel);
                }

                private static function reportRunningTestCase(TestCase $testCase): void
                {
                    $progressFile = self::PROGRESS_FILE;
                    if ($progressFile === '') {
                        return;
                    }

                    $handle = @fopen($progressFile, 'ab');
                    if ($handle === false) {
                        return;
                    }

                    if (!flock($handle, LOCK_EX)) {
                        fclose($handle);

                        return;
                    }

                    fwrite($handle, $testCase->getName() . PHP_EOL);
                    fflush($handle);
                    flock($handle, LOCK_UN);
                    fclose($handle);
                }
            }
            PHP;

        $content = str_replace(
            [
                '__APITESTER_PARENT__',
                '__APITESTER_CONFIG__',
                '__APITESTER_SUITE__',
                '__APITESTER_OPTIONS__',
                '__APITESTER_PROGRESS_FILE__',
            ],
            [$parent, $configExport, $suiteExport, $optionsExport, $progressFileExport],
            $content
        );

        file_put_contents($file, ltrim($content));
        $this->progressFiles[$file] = $progressFile;

        return $file;
    }

    final public function cleanupRunnerFile(string $testFile): void
    {
        if (isset($this->progressFiles[$testFile])) {
            $progressFile = $this->progressFiles[$testFile];
            if (is_file($progressFile)) {
                unlink($progressFile);
            }
            unset($this->progressFiles[$testFile]);
        }

        if (is_file($testFile)) {
            unlink($testFile);
        }

        $dir = \dirname($testFile);
        if (!is_dir($dir) || !str_starts_with(basename($dir), 'api-tester-runner-')) {
            return;
        }

        $directoryContent = scandir($dir);
        if (!is_array($directoryContent)) {
            return;
        }

        if (\count(array_diff($directoryContent, ['.', '..'])) > 0) {
            return;
        }

        @rmdir($dir);
    }

    /**
     * @param array<string, mixed> $passThroughOptions
     * @param callable(string): void $writeOutput
     */
    final public function run(
        array $passThroughOptions,
        Config\Suite $suiteConfig,
        callable $writeOutput,
        string $testFile
    ): int {
        $binary = $this->findBinary();

        if (!is_file($testFile)) {
            throw new \RuntimeException("APITester runner file not found at '{$testFile}'.");
        }

        $arguments = $this->buildArguments($passThroughOptions, $suiteConfig);

        $command = array_merge(
            [PHP_BINARY, $binary],
            $this->getRunnerSpecificArgs($testFile),
            $arguments,
            [$testFile]
        );

        $progressFile = $this->progressFiles[$testFile] ?? null;

        $process = new Process($command, Path::getBasePath());
        $process->setTimeout(null);
        $runningPrefix = '[running]';
        $statusPrefix = '[status]';

        $flushOutput = static function (string $buffer) use ($writeOutput): bool {
            if ($buffer === '') {
                return false;
            }

            $writeOutput($buffer);

            return true;
        };

        $progressOffset = 0;
        $progressRemainder = '';
        $flushProgress = static function (bool $flushRemainder = false) use (
            $progressFile,
            $writeOutput,
            $runningPrefix,
            &$progressOffset,
            &$progressRemainder
        ): int {
            if (!is_string($progressFile) || $progressFile === '') {
                return 0;
            }

            clearstatcache(true, $progressFile);
            if (!is_file($progressFile)) {
                return 0;
            }

            $emittedLinesCount = 0;
            $chunk = file_get_contents($progressFile, false, null, $progressOffset);
            if (is_string($chunk) && $chunk !== '') {
                $progressOffset += strlen($chunk);
                $progressRemainder .= str_replace(["\r\n", "\r"], "\n", $chunk);
            }

            while (($lineEnd = strpos($progressRemainder, "\n")) !== false) {
                $line = substr($progressRemainder, 0, $lineEnd);
                $progressRemainder = substr($progressRemainder, $lineEnd + 1);

                if ($line === '') {
                    continue;
                }

                $writeOutput("{$runningPrefix} {$line}\n");
                ++$emittedLinesCount;
            }

            if ($flushRemainder && $progressRemainder !== '') {
                $writeOutput("{$runningPrefix} {$progressRemainder}\n");
                $progressRemainder = '';
                ++$emittedLinesCount;
            }

            return $emittedLinesCount;
        };

        try {
            $process->start();
            $lastActivityAt = microtime(true);
            $startedTestsCount = 0;
            $statusIntervalSeconds = 10;

            while ($process->isRunning()) {
                $hasOutput = $flushOutput($process->getIncrementalOutput());
                $hasOutput = $flushOutput($process->getIncrementalErrorOutput()) || $hasOutput;

                $progressLinesCount = $flushProgress();
                if ($progressLinesCount > 0) {
                    $startedTestsCount += $progressLinesCount;
                    $hasOutput = true;
                }

                if ($hasOutput) {
                    $lastActivityAt = microtime(true);
                } elseif ((microtime(true) - $lastActivityAt) >= $statusIntervalSeconds) {
                    $writeOutput("{$statusPrefix} still running ({$startedTestsCount} tests started)\n");
                    $lastActivityAt = microtime(true);
                }

                usleep(200000);
            }

            $flushOutput($process->getIncrementalOutput());
            $flushOutput($process->getIncrementalErrorOutput());
            $flushProgress(true);

            return $process->getExitCode() ?? 1;
        } finally {
            if (is_string($progressFile) && is_file($progressFile)) {
                unlink($progressFile);
            }
            unset($this->progressFiles[$testFile]);
        }
    }

    abstract protected function getBinaryName(): string;

    /**
     * @return list<string>
     */
    abstract protected function getRunnerSpecificArgs(string $testFile): array;

    private function findBinary(): string
    {
        $basePath = Path::getBasePath();
        $name = $this->getBinaryName();

        foreach ([$basePath . '/vendor/bin/' . $name, $basePath . '/bin/' . $name] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException(
            "{$name} binary not found. Looked in '{$basePath}/vendor/bin/{$name}' and '{$basePath}/bin/{$name}'."
        );
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<string>
     */
    private function buildArguments(array $options, Config\Suite $suiteConfig): array
    {
        $args = ['--colors=auto'];

        $phpunitConfig = $suiteConfig->getPhpunitConfig();
        if ($phpunitConfig !== null) {
            $args[] = '--configuration=' . $phpunitConfig;
        }

        foreach ($options as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $args[] = "--{$key}";
                continue;
            }
            if (!\is_scalar($value)) {
                throw new \InvalidArgumentException('Options must be scalar');
            }

            $args[] = "--{$key}={$value}";
        }

        return $args;
    }
}
