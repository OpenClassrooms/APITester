<?php

declare(strict_types=1);

namespace APITester\Command;

use APITester\Authenticator\Exception\AuthenticationException;
use APITester\Authenticator\Exception\AuthenticationLoadingException;
use APITester\Config;
use APITester\Config\Exception\ConfigurationException;
use APITester\Definition\Loader\Exception\DefinitionLoaderNotFoundException;
use APITester\Definition\Loader\Exception\DefinitionLoadingException;
use APITester\Preparator\Exception\InvalidPreparatorConfigException;
use APITester\Requester\Exception\RequesterNotFoundException;
use APITester\Test\Exception\SuiteNotFoundException;
use APITester\Test\Plan;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class ExecutePlanCommand extends Command
{
    protected static $defaultName = 'launch';

    private InputInterface $input;

    private OutputInterface $output;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     * @throws InvalidPreparatorConfigException
     * @throws RequesterNotFoundException
     * @throws AuthenticationLoadingException
     * @throws ConfigurationException
     * @throws AuthenticationException
     * @throws SuiteNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->printInfo();
        $this->validateOptions();

        $testPlan = $this->initPlan();

        return (int) !$testPlan->execute(
            Config\Loader\PlanConfigLoader::load((string) $this->input->getOption('config')),
            (string) $this->input->getOption('suite'),
            $input->getOptions()
        );
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
                'testdox',
                null,
                InputOption::VALUE_NONE,
                'testdox print format'
            )
            ->addOption(
                'coverage-php',
                null,
                InputOption::VALUE_OPTIONAL,
                'coverage export to php format'
            )
            ->addOption(
                'coverage-clover',
                null,
                InputOption::VALUE_OPTIONAL,
                'coverage export to clover format'
            )
            ->addOption(
                'coverage-html',
                null,
                InputOption::VALUE_OPTIONAL,
                'coverage export to html format'
            )
            ->addOption(
                'coverage-text',
                null,
                InputOption::VALUE_OPTIONAL,
                'coverage export to html format'
            )
            ->addOption(
                'coverage-cobertura',
                null,
                InputOption::VALUE_OPTIONAL,
                'coverage export to html format'
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
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Filter which tests to run'
            )
            ->addOption(
                'log-junit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Log test execution in JUnit XML format to file'
            )
            ->addOption(
                'log-teamcity',
                null,
                InputOption::VALUE_OPTIONAL,
                'Log test execution in JUnit XML format to file'
            )
            ->addOption(
                'testdox-html',
                null,
                InputOption::VALUE_OPTIONAL,
                'Write agile documentation in HTML format to file'
            )
            ->addOption(
                'testdox-text',
                null,
                InputOption::VALUE_OPTIONAL,
                'Write agile documentation in Text format to file'
            )
            ->addOption(
                'testdox-xml',
                null,
                InputOption::VALUE_OPTIONAL,
                'Write agile documentation in XML format to file'
            )
            ->addOption(
                'part',
                null,
                InputOption::VALUE_OPTIONAL,
                'Partition tests into groups and run only one of them, ex: --part=1/3'
            )
        ;
    }

    private function initPlan(): Plan
    {
        $testPlan = new Plan();
        $testPlan->setLogger(new ConsoleLogger($this->output));

        return $testPlan;
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
    }

    private function validateOptions(): void
    {
        if ($this->input->getOption('part') !== null) {
            $part = explode('/', (string) $this->input->getOption('part'));
            if (\count($part) !== 2) {
                throw new \InvalidArgumentException('The part option must be in the format x/y where y > 0 and x <= y');
            }
            if ($part[0] > $part[1] || $part[1] <= 0) {
                throw new \InvalidArgumentException('The part option must be in the format x/y where y > 0 and x <= y');
            }
        }
    }
}
