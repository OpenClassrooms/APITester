<?php

declare(strict_types=1);

namespace OpenAPITesting\Command;

use OpenAPITesting\Authenticator\Exception\AuthenticationException;
use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Config;
use OpenAPITesting\Config\Exception\ConfigurationException;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoaderNotFoundException;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException;
use OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Requester\Exception\RequesterNotFoundException;
use OpenAPITesting\Test\Exception\SuiteNotFoundException;
use OpenAPITesting\Test\Plan;
use Psr\Http\Client\ClientExceptionInterface;
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
     * @throws PreparatorLoadingException
     * @throws RequesterNotFoundException
     * @throws ClientExceptionInterface
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
        $testPlan->execute($config, $input->getOption('suite'), $input->getOptions());

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
