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
    public function testLaunchPreservesRunnerOutcome(int $statusCode, int $expectedExitCode): void
    {
        $configPath = tempnam(sys_get_temp_dir(), 'api-tester-console-');
        self::assertNotFalse($configPath);
        $junit = $configPath . '.xml';
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
                '--log-junit' => $junit,
            ]);

            self::assertSame($expectedExitCode, $status, $tester->getDisplay());
            self::assertFileExists($junit, $tester->getDisplay());
            $report = simplexml_load_file($junit);
            self::assertNotFalse($report);
            $cases = $report->xpath('//testcase');
            $failures = $report->xpath('//testcase/failure');
            self::assertIsArray($cases);
            self::assertIsArray($failures);
            self::assertCount(4, $cases);
            self::assertCount($expectedExitCode === 0 ? 0 : 4, $failures);
            self::assertStringNotContainsString('PHPUnit Deprecation', $tester->getDisplay());
        } finally {
            unlink($configPath);
            if (is_file($junit)) {
                unlink($junit);
            }
        }
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function executionOutcomes(): iterable
    {
        yield 'successful responses' => [200, 0];
        yield 'unexpected responses' => [201, 1];
    }
}
