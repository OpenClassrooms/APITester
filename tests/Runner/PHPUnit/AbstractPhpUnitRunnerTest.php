<?php

declare(strict_types=1);

namespace APITester\Tests\Runner\PHPUnit;

use APITester\Config\Definition;
use APITester\Config\Suite;
use APITester\Runner\PHPUnit\PhpUnitRunner;
use PHPUnit\Framework\TestCase;

final class AbstractPhpUnitRunnerTest extends TestCase
{
    private ?string $generatedFile = null;

    protected function tearDown(): void
    {
        if ($this->generatedFile !== null) {
            $runner = new PhpUnitRunner();
            $runner->cleanupRunnerFile($this->generatedFile);
            $this->generatedFile = null;
        }
    }

    public function testCreateRunnerFileGeneratesValidPhpFile(): void
    {
        $runner = new PhpUnitRunner();
        $suite = $this->createSuite();

        $this->generatedFile = $runner->createRunnerFile($suite, '/tmp/config.yaml', 'my-suite', [
            'verbosity' => 32,
        ]);

        static::assertFileExists($this->generatedFile);
        static::assertStringContainsString('/api-tester-runner-', str_replace('\\', '/', $this->generatedFile));
        static::assertStringEndsWith('.php', $this->generatedFile);

        $content = file_get_contents($this->generatedFile);
        static::assertIsString($content);
        static::assertStringStartsWith('<?php', $content);
    }

    public function testGeneratedFileContainsCorrectValues(): void
    {
        $runner = new PhpUnitRunner();
        $suite = $this->createSuite();

        $this->generatedFile = $runner->createRunnerFile(
            $suite,
            '/path/to/config.yaml',
            'test-suite',
            [
                'debug' => true,
            ]
        );

        $content = file_get_contents($this->generatedFile);
        static::assertIsString($content);

        static::assertStringContainsString('/path/to/config.yaml', $content);
        static::assertStringContainsString('test-suite', $content);
        static::assertStringContainsString('ApiTesterRunnerTest', $content);
        static::assertStringContainsString('$seenDataSetNames = [];', $content);
        static::assertStringContainsString('$dataSetName = $testCase->getName();', $content);
    }

    public function testCleanupRunnerFileDeletesFile(): void
    {
        $runner = new PhpUnitRunner();
        $suite = $this->createSuite();

        $file = $runner->createRunnerFile($suite, '/tmp/config.yaml', 'suite', []);
        $directory = \dirname($file);

        static::assertFileExists($file);
        static::assertDirectoryExists($directory);

        $runner->cleanupRunnerFile($file);

        static::assertFileDoesNotExist($file);
        static::assertDirectoryDoesNotExist($directory);
        $this->generatedFile = null;
    }

    public function testCleanupRunnerFileKeepsDirectoryWhenItContainsOtherFiles(): void
    {
        $runner = new PhpUnitRunner();
        $suite = $this->createSuite();

        $file = $runner->createRunnerFile($suite, '/tmp/config.yaml', 'suite', []);
        $directory = \dirname($file);
        $otherFile = $directory . '/other.tmp';
        file_put_contents($otherFile, 'tmp');

        $runner->cleanupRunnerFile($file);

        static::assertFileDoesNotExist($file);
        static::assertFileExists($otherFile);
        static::assertDirectoryExists($directory);

        unlink($otherFile);
        rmdir($directory);
    }

    /**
     * @dataProvider buildArgumentsProvider
     *
     * @param array<string, mixed> $options
     * @param list<string>         $expectedContains
     * @param list<string>         $expectedNotContains
     */
    public function testBuildArguments(
        array $options,
        ?string $phpunitConfig,
        array $expectedContains,
        array $expectedNotContains
    ): void {
        $ref = new \ReflectionClass(PhpUnitRunner::class);
        $method = $ref->getMethod('buildArguments');

        $suite = $this->createSuite();
        if ($phpunitConfig !== null) {
            $suite->setPhpunitConfig($phpunitConfig);
        }

        $runner = new PhpUnitRunner();
        /** @var list<string> $args */
        $args = $method->invoke($runner, $options, $suite);

        foreach ($expectedContains as $expected) {
            static::assertContains($expected, $args);
        }

        foreach ($expectedNotContains as $notExpected) {
            static::assertNotContains($notExpected, $args);
        }
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: ?string, 2: list<string>, 3: list<string>}>
     */
    public static function buildArgumentsProvider(): iterable
    {
        yield 'boolean true produces flag' => [
            [
                'verbose' => true,
            ],
            null,
            ['--verbose', '--colors=auto'],
            [],
        ];

        yield 'null and false are skipped' => [
            [
                'skip-me' => null,
                'also-skip' => false,
            ],
            null,
            ['--colors=auto'],
            ['--skip-me', '--also-skip'],
        ];

        yield 'scalar produces key=value' => [
            [
                'filter' => 'MyTest',
            ],
            null,
            ['--filter=MyTest', '--colors=auto'],
            [],
        ];

        yield 'phpunit config is included' => [
            [],
            'custom-phpunit.xml',
            ['--configuration=custom-phpunit.xml', '--colors=auto'],
            [],
        ];
    }

    private function createSuite(): Suite
    {
        return new Suite('test', new Definition('tests/Fixtures/OpenAPI/petstore.yaml', 'openapi'));
    }
}
