<?php

declare(strict_types=1);

namespace APITester\Tests\Runner\PHPUnit;

use APITester\Runtime\Config\Loader\PlanConfigLoader;
use APITester\Test\Runner\ParaTest\ParaTestRunner;
use APITester\Test\Runner\PHPUnit\PhpUnitRunner;
use APITester\Test\Runner\TestRunner;
use APITester\Tests\Fixtures\Runner\Kernel;
use APITester\Tests\Fixtures\Runner\ParentTestCase;
use APITester\Util\Path;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;
use Symfony\Component\Yaml\Yaml;

final class RunnerExecutionTest extends TestCase
{
    public function testGeneratedCasesExecuteWithBothRunners(): void
    {
        $this->assertCasesExecute('openapi.yaml', [new PhpUnitRunner(), new ParaTestRunner(2)]);
    }

    public function testDuplicateDataSetNamesRemainDistinct(): void
    {
        $runners = [new PhpUnitRunner()];
        // ParaTest 7.4 uses unanchored dataset filters and reruns prefix matches on PHPUnit 10.
        if (version_compare(Version::id(), '11.0', '>=')) {
            $runners[] = new ParaTestRunner(2);
        }
        $this->assertCasesExecute('duplicate-names.yaml', $runners);
    }

    /**
     * @param list<TestRunner> $runners
     */
    private function assertCasesExecute(string $definition, array $runners): void
    {
        $basePath = Path::getBasePath();
        $directory = $basePath . '/build/runner-' . bin2hex(random_bytes(8));
        mkdir($directory, 0777, true);
        $configPath = $directory . '/api-tester.yaml';
        $config = [
            'suites' => [[
                'name' => 'runner',
                'definition' => [
                    'path' => 'tests/Fixtures/Runner/' . $definition,
                    'format' => 'openapi',
                ],
                'requester' => 'symfony-kernel',
                'symfonyKernelClass' => Kernel::class,
                'testCaseClass' => ParentTestCase::class,
                'preparators' => [[
                    'name' => 'examples',
                    'excludedFields' => ['body'],
                ]],
            ]],
        ];

        try {
            foreach ($runners as $index => $runner) {
                foreach ([false, true] as $fail) {
                    $config['suites'][0]['preparators'][0]['response']['statusCode'] = $fail ? 201 : 200;
                    file_put_contents($configPath, Yaml::dump($config, 10));
                    $suite = PlanConfigLoader::load($configPath)->getSuites()[0];
                    $file = $runner->createRunnerFile($suite, $configPath, 'runner', [
                        'verbosity' => 16,
                    ]);
                    $coverage = $directory . '/coverage-' . $index . '-' . (int) $fail . '.xml';
                    $output = '';

                    try {
                        $status = $runner->run([
                            'bootstrap' => $basePath . '/vendor/autoload.php',
                            'no-configuration' => true,
                            'cache-directory' => $basePath . '/build/runner-cache',
                            'coverage-filter' => \dirname(__DIR__, 3) . '/src/Runtime/Requester',
                            'coverage-clover' => $coverage,
                        ], $suite, static function (string $buffer) use (&$output): void {
                            $output .= $buffer;
                        }, $file);

                        static::assertSame($fail ? 1 : 0, $status, $output);
                        static::assertStringContainsString($fail ? 'Tests: 4' : 'OK (4 tests,', $output);
                        static::assertStringNotContainsString('PHPUnit Deprecation', $output);
                        if ($fail && $definition === 'duplicate-names.yaml') {
                            static::assertStringContainsString('examples - runner - named #2 #2', $output);
                            static::assertStringContainsString('examples - runner - named #3', $output);
                        }
                        if (extension_loaded('pcov') || extension_loaded('xdebug')) {
                            static::assertFileExists($coverage, $output);
                            $xml = simplexml_load_file($coverage);
                            static::assertNotFalse($xml);
                            static::assertNotEmpty($xml->xpath('//line[@count > 0]'), $output);
                        }
                    } finally {
                        $runner->cleanupRunnerFile($file);
                    }
                }
            }
        } finally {
            foreach (new \DirectoryIterator($directory) as $file) {
                if ($file->isFile()) {
                    unlink($file->getPathname());
                }
            }
            rmdir($directory);
        }
    }
}
