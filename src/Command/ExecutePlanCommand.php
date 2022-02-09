<?php

declare(strict_types=1);

namespace OpenAPITesting\Command;

use OpenAPITesting\Authenticator\Exception\AuthenticationException;
use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Authenticator\Exception\AuthenticatorNotFoundException;
use OpenAPITesting\Config;
use OpenAPITesting\Config\Exception\ConfigurationException;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoaderNotFoundException;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException;
use OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Requester\Exception\RequesterNotFoundException;
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
     * @throws AuthenticatorNotFoundException
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     * @throws InvalidPreparatorConfigException
     * @throws PreparatorLoadingException
     * @throws RequesterNotFoundException
     * @throws ClientExceptionInterface
     * @throws AuthenticationLoadingException
     * @throws ConfigurationException
     * @throws AuthenticationException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFilePath = $input->getOption('config');
        $testPlan = new Plan();
        $testPlan->setLogger(new ConsoleLogger($output));
        $config = Config\Loader\PlanConfigLoader::load((string) $configFilePath);
        $testPlan->execute($config);

//        $suite = new TestSuite();
//        $suite->addTestSuite('Test');
//        TestRunner::run($suite);

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
        ;
    }
}
