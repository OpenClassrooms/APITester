<?php

declare(strict_types=1);

namespace OpenAPITesting\Command;

use OpenAPITesting\Config\Loader\PlanConfigLoader;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Requester\HttpAsyncRequester;
use OpenAPITesting\Test\Plan;
use OpenAPITesting\Test\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Test\Preparator\Status404TestCasesPreparator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class ExecutePlanCommand extends Command
{
    protected static $defaultName = 'launch';

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

    /**
     * @throws \OpenAPITesting\Test\LoaderNotFoundException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \OpenAPITesting\Definition\Loader\DefinitionLoadingException
     * @throws \OpenAPITesting\Test\RequesterNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $preparators = [
            new OpenApiExamplesTestCasesPreparator(),
            new Status404TestCasesPreparator(),
        ];
        $requesters = [
            new HttpAsyncRequester(),
        ];
        $loaders = [
            new OpenApiDefinitionLoader(),
        ];

        /** @var string $configFilePath */
        $configFilePath = $input->getOption('config');

        $testPlan = new Plan(
            $preparators,
            $requesters,
            $loaders,
            new ConsoleLogger($output),
        );
        $config = (new PlanConfigLoader())($configFilePath);
        $testPlan->execute($config);

        return 1;
    }
}
