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
        $configFilePath = $input->getOption('config');
        $testPlan = new Plan();
        $testPlan->setLogger(new ConsoleLogger($output));
        $config = Config\Loader\PlanConfigLoader::load((string) $configFilePath);
        /** @var string|null $suiteName */
        $suiteName = $input->getOption('suite');
        $testPlan->execute($config, $suiteName, $input->getOptions());

        return 1;
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
                'log-junit',
                null,
                InputOption::VALUE_OPTIONAL,
                'report file to create',
                false
            )
            ->addOption(
                'testdox',
                null,
                InputOption::VALUE_NONE,
                'testdox print format'
            )
        ;
    }
}
