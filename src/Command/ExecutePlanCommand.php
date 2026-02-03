<?php

declare(strict_types=1);

namespace APITester\Command;

use APITester\Config;
use APITester\Config\Exception\ConfigurationException;
use APITester\Runner\ParaTest\ParaTestRunner;
use APITester\Runner\PHPUnit\PhpUnitRunner;
use APITester\Runner\TestRunner;
use APITester\Test\Exception\SuiteNotFoundException;
use APITester\Test\Plan;
use DOMDocument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class ExecutePlanCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'launch';

    /**
     * @var list<string>
     */
    private array $passThroughOptionNames = [];

    private InputInterface $input;

    private OutputInterface $output;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @throws ConfigurationException
     * @throws SuiteNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->printInfo();
        $this->validateOptions();

        $configPath = (string) $this->input->getOption('config');
        $suiteName = (string) $this->input->getOption('suite');
        $processes = $this->getProcesses();

        $setBaseline = $this->input->getOption('set-baseline') !== false;
        $needsBaselineUpdate = $setBaseline
            || $this->input->getOption('update-baseline') !== false;
        if ($needsBaselineUpdate && $processes > 1) {
            throw new \InvalidArgumentException(
                'Baseline update is not supported with --processes>1. Run without --processes.'
            );
        }

        $planConfig = Config\Loader\PlanConfigLoader::load($configPath);
        $plan = new Plan();
        $plan->setLogger(new ConsoleLogger($this->output));
        $suiteConfig = $plan->getSuiteConfig($planConfig, $suiteName);

        $runner = $this->createRunner($processes);
        $runnerOptions = $this->buildRunnerOptions();
        $testFile = $runner->createRunnerFile($suiteConfig, $configPath, $suiteName, $runnerOptions);

        if ($setBaseline) {
            $baselineFile = $suiteConfig->getFilters()->getBaseline();
            if (file_exists($baselineFile)) {
                unlink($baselineFile);
            }
        }

        $passThroughOptions = $this->getPassThroughOptions();
        $junitFile = $this->resolveJUnitFile($needsBaselineUpdate);
        $isAutoJunit = $junitFile !== null && !is_string($this->input->getOption('log-junit'));
        if ($junitFile !== null) {
            $passThroughOptions['log-junit'] = $junitFile;
        }

        $exitCode = $runner->run($passThroughOptions, $suiteConfig, $this->output->write(...), $testFile);

        if ($needsBaselineUpdate && $junitFile !== null) {
            $this->updateBaseline($suiteConfig, $junitFile);
        }

        if ($isAutoJunit) {
            unlink($junitFile);
        }

        $runner->cleanupRunnerFile($testFile);

        return $exitCode;
    }

    protected function configure(): void
    {
        $this->setDescription('launch test plan')
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                'config file',
                'api-tester.yaml'
            )
            ->addOption(
                'suite',
                's',
                InputOption::VALUE_OPTIONAL,
                'suite name to run',
            )
            ->addOption(
                'processes',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Run tests in parallel using ParaTest (requires brianium/paratest).',
                1
            )
            ->addOption(
                'set-baseline',
                null,
                InputOption::VALUE_NONE,
                'if set it will create a baseline file that will register all errors so they become ignored on the next run'
            )
            ->addOption(
                'update-baseline',
                null,
                InputOption::VALUE_NONE,
                'update baseline with new errors to ignore'
            )
            ->addOption(
                'ignore-baseline',
                null,
                InputOption::VALUE_NONE,
                'ignore baseline file'
            )
            ->addOption(
                'only-baseline',
                null,
                InputOption::VALUE_NONE,
                'only execute tests from the baseline'
            )
            ->addOption(
                'part',
                null,
                InputOption::VALUE_OPTIONAL,
                'Partition tests into groups and run only one of them, ex: --part=1/3'
            )
            ->addOption(
                'operation-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'takes an operation-id to load from api definition'
            )
        ;

        $this
            ->addPassThroughOption('testdox', null, InputOption::VALUE_NONE, 'testdox print format')
            ->addPassThroughOption('coverage-php', null, InputOption::VALUE_OPTIONAL, 'coverage export to php format')
            ->addPassThroughOption(
                'coverage-clover',
                null,
                InputOption::VALUE_OPTIONAL,
                'coverage export to clover format'
            )
            ->addPassThroughOption('coverage-html', null, InputOption::VALUE_OPTIONAL, 'coverage export to html format')
            ->addPassThroughOption('coverage-text', null, InputOption::VALUE_OPTIONAL, 'coverage export to text format')
            ->addPassThroughOption(
                'coverage-cobertura',
                null,
                InputOption::VALUE_OPTIONAL,
                'coverage export to cobertura format'
            )
            ->addPassThroughOption('filter', null, InputOption::VALUE_OPTIONAL, 'Filter which tests to run')
            ->addPassThroughOption(
                'log-junit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Log test execution in JUnit XML format to file'
            )
            ->addPassThroughOption(
                'log-teamcity',
                null,
                InputOption::VALUE_OPTIONAL,
                'Log test execution in TeamCity format to file'
            )
            ->addPassThroughOption(
                'testdox-html',
                null,
                InputOption::VALUE_OPTIONAL,
                'Write agile documentation in HTML format to file'
            )
            ->addPassThroughOption(
                'testdox-text',
                null,
                InputOption::VALUE_OPTIONAL,
                'Write agile documentation in Text format to file'
            )
            ->addPassThroughOption(
                'testdox-xml',
                null,
                InputOption::VALUE_OPTIONAL,
                'Write agile documentation in XML format to file'
            )
        ;
    }

    private function addPassThroughOption(
        string $name,
        ?string $shortcut,
        ?int $mode,
        string $description,
        mixed $default = null
    ): static {
        $this->passThroughOptionNames[] = $name;
        $this->addOption($name, $shortcut, $mode, $description, $default);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    private function getPassThroughOptions(): array
    {
        $options = [];
        foreach ($this->passThroughOptionNames as $name) {
            $options[$name] = $this->input->getOption($name);
        }

        return $options;
    }

    private function createRunner(int $processes): TestRunner
    {
        if ($processes > 1) {
            return new ParaTestRunner($processes);
        }

        return new PhpUnitRunner();
    }

    private function printInfo(): void
    {
        if ($this->input->getOption('set-baseline') !== false) {
            $this->output->writeln('Creating baseline after tests run.');
        }
        if ($this->input->getOption('update-baseline') !== false) {
            $this->output->writeln('Updating baseline after tests run.');
        }
        if ($this->input->getOption('ignore-baseline') !== false) {
            $this->output->writeln('Ignoring baseline.');
        }
        if ($this->input->getOption('only-baseline') !== false) {
            $this->output->writeln('Only executing tests from the baseline.');
        }
    }

    private function validateOptions(): void
    {
        $this->getProcesses();

        if ($this->input->getOption('part') !== null) {
            $part = explode('/', (string) $this->input->getOption('part'));
            if (\count($part) !== 2 || $part[0] > $part[1] || $part[1] <= 0) {
                throw new \InvalidArgumentException('The part option must be in the format x/y where y > 0 and x <= y');
            }
        }
    }

    private function getProcesses(): int
    {
        $processes = filter_var(
            $this->input->getOption('processes'),
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );
        if ($processes === false) {
            throw new \InvalidArgumentException('The processes option must be >= 1');
        }

        return $processes;
    }

    private function resolveJUnitFile(bool $needsBaselineUpdate): ?string
    {
        $junit = $this->input->getOption('log-junit');
        if (is_string($junit) && $junit !== '') {
            return $junit;
        }

        if (!$needsBaselineUpdate) {
            return null;
        }

        $file = tempnam(sys_get_temp_dir(), 'api-tester-');
        if ($file === false) {
            throw new \RuntimeException('Could not create a temporary JUnit file.');
        }

        return $file;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRunnerOptions(): array
    {
        $runnerOptions = [];
        $part = $this->input->getOption('part');
        if ($part !== null) {
            $runnerOptions['part'] = (string) $part;
        }
        $operationId = $this->input->getOption('operation-id');
        if ($operationId !== null) {
            $runnerOptions['operation-id'] = (string) $operationId;
        }
        if ($this->input->getOption('ignore-baseline') !== false) {
            $runnerOptions['ignore-baseline'] = true;
        }
        if ($this->input->getOption('only-baseline') !== false) {
            $runnerOptions['only-baseline'] = true;
            $runnerOptions['ignore-baseline'] = true;
        }

        return $runnerOptions;
    }

    private function updateBaseline(Config\Suite $suiteConfig, string $junitFile): void
    {
        if (!is_file($junitFile)) {
            $this->output->writeln("JUnit file not found: {$junitFile}");

            return;
        }

        $failed = $this->extractFailedTestCaseNames($junitFile);
        $exclude = array_map(static fn (string $name): array => [
            'testcase.name' => $name,
        ], $failed);

        $suiteConfig->getFilters()->writeBaseline($exclude);
    }

    /**
     * @return list<string>
     */
    private function extractFailedTestCaseNames(string $junitFile): array
    {
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->load($junitFile);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true) {
            return [];
        }

        $failed = [];

        foreach ($doc->getElementsByTagName('testcase') as $testcase) {
            if (!$testcase instanceof \DOMElement) {
                continue;
            }

            $name = $testcase->getAttribute('name');
            $pattern = '/^testApi with data set "(?<name>.*)"$/';
            if (preg_match($pattern, $name, $m) !== 1) {
                continue;
            }

            $hasFailure = $testcase->getElementsByTagName('failure')->length > 0
                || $testcase->getElementsByTagName('error')->length > 0;
            if (!$hasFailure) {
                continue;
            }

            $failed[] = $m['name'];
        }

        /** @var list<string> */
        return array_values(array_unique($failed));
    }
}
