<?php

declare(strict_types=1);

namespace APITester\Tests\Runtime\Console;

use APITester\Runtime\Console\ExecutePlanCommand;
use APITester\Tests\Fixtures\Runner\Kernel;
use APITester\Tests\Fixtures\Runner\ParentTestCase;
use APITester\Util\Path;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

final class ExecutePlanCommandTest extends TestCase
{
    public function testApplicationListsLaunchCommand(): void
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->addCommands([new ExecutePlanCommand()]);
        $tester = new ApplicationTester($application);

        $status = $tester->run([
            'command' => 'list',
            '--raw' => true,
        ]);

        self::assertSame(0, $status);
        self::assertStringContainsString('launch', $tester->getDisplay());
        self::assertStringContainsString('launch test plan', $tester->getDisplay());
    }

    public function testApplicationShowsLaunchHelp(): void
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->addCommands([new ExecutePlanCommand()]);
        $tester = new ApplicationTester($application);

        $status = $tester->run([
            'command' => 'help',
            'command_name' => 'launch',
        ]);

        self::assertSame(0, $status);
        self::assertStringContainsString('launch [options]', $tester->getDisplay());
        self::assertStringContainsString('--config', $tester->getDisplay());
        self::assertStringContainsString('--suite', $tester->getDisplay());
        self::assertStringContainsString('--processes', $tester->getDisplay());
    }

    #[DataProvider('executionOutcomes')]
    public function testLaunchPreservesRunnerOutcome(
        int $statusCode,
        int $expectedExitCode,
        string $expectedSummary
    ): void {
        $configPath = tempnam(sys_get_temp_dir(), 'api-tester-console-');
        self::assertNotFalse($configPath);
        $config = [
            'suites' => [[
                'name' => 'console',
                'definition' => [
                    'path' => 'tests/Fixtures/Runner/openapi.yaml',
                    'format' => 'openapi',
                ],
                'requester' => 'symfony-kernel',
                'symfonyKernelClass' => Kernel::class,
                'testCaseClass' => ParentTestCase::class,
                'phpunitConfig' => Path::getBasePath() . '/config/phpunit.xml',
                'preparators' => [[
                    'name' => 'examples',
                    'excludedFields' => ['body'],
                    'response' => [
                        'statusCode' => $statusCode,
                    ],
                ]],
            ]],
        ];
        file_put_contents($configPath, Yaml::dump($config, 10));
        $tester = new CommandTester(new ExecutePlanCommand());

        try {
            $status = $tester->execute([
                '--config' => $configPath,
                '--suite' => 'console',
            ]);

            self::assertSame($expectedExitCode, $status, $tester->getDisplay());
            self::assertStringContainsString($expectedSummary, $tester->getDisplay());
            self::assertStringNotContainsString('PHPUnit Deprecation', $tester->getDisplay());
        } finally {
            unlink($configPath);
        }
    }

    /**
     * @return iterable<string, array{int, int, string}>
     */
    public static function executionOutcomes(): iterable
    {
        yield 'successful responses' => [200, 0, 'OK (4 tests,'];
        yield 'unexpected responses' => [201, 1, 'Tests: 4'];
    }
}
