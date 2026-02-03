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

        $dir = sys_get_temp_dir() . '/api-tester-' . bin2hex(random_bytes(8));
        if (!mkdir($dir) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create a temporary directory for the runner file.');
        }
        $file = $dir . '/ApiTesterRunnerTest.php';

        $parent = '\\' . $testCaseClass;
        $configExport = var_export($configPath, true);
        $suiteExport = var_export($suiteName, true);
        $optionsExport = var_export($runnerOptions, true);

        $content = <<<'PHP'
            <?php

            declare(strict_types=1);

            use APITester\Config\Loader\PlanConfigLoader;
            use APITester\Test\Plan;
            use APITester\Test\TestCase;
            use Symfony\Component\HttpKernel\HttpKernelInterface;

            final class ApiTesterRunnerTest extends __APITESTER_PARENT__
            {
                private const CONFIG_PATH = __APITESTER_CONFIG__;
                private const SUITE_NAME = __APITESTER_SUITE__;
                private const OPTIONS = __APITESTER_OPTIONS__;

                /**
                 * @return iterable<string, array{0: TestCase}>
                 */
                public static function apiTestCases(): iterable
                {
                    $config = PlanConfigLoader::load(self::CONFIG_PATH);
                    $plan = new Plan();

                    foreach ($plan->getTestCases($config, self::SUITE_NAME, self::OPTIONS) as $testCase) {
                        yield $testCase->getName() => [$testCase];
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
                    $kernel = null;
                    if (method_exists($this, 'getKernel')) {
                        $candidate = $this->getKernel();
                        if ($candidate instanceof HttpKernelInterface) {
                            $kernel = $candidate;
                        }
                    }

                    $testCase->test($kernel);
                }
            }
            PHP;

        $content = str_replace(
            ['__APITESTER_PARENT__', '__APITESTER_CONFIG__', '__APITESTER_SUITE__', '__APITESTER_OPTIONS__'],
            [$parent, $configExport, $suiteExport, $optionsExport],
            $content
        );

        file_put_contents($file, ltrim($content));

        return $file;
    }

    final public function cleanupRunnerFile(string $testFile): void
    {
        if (is_file($testFile)) {
            unlink($testFile);
        }

        $dir = \dirname($testFile);
        if (is_dir($dir) && str_starts_with(basename($dir), 'api-tester-')) {
            rmdir($dir);
        }
    }

    /**
     * @param array<string, mixed>   $passThroughOptions
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

        $process = new Process($command, Path::getBasePath());
        $process->setTimeout(null);
        $process->run(static fn (string $_type, string $buffer) => $writeOutput($buffer));

        return $process->getExitCode() ?? 1;
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
        $args = ['--colors=always'];

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
