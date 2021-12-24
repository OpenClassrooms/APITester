<?php

declare(strict_types=1);

namespace OpenAPITesting\Command;

use OpenAPITesting\Config\Loader\TestPlanConfigLoader;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Test\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Test\TestPlan;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class ExecuteTestPlanCommand extends Command
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $preparators = [
            new OpenApiExamplesTestCasesPreparator(),
        ];
        $loaders = [
            new OpenApiDefinitionLoader(),
        ];

        /** @var string $configFilePath */
        $configFilePath = $input->getOption('config');

        $testPlan = new TestPlan($preparators, $loaders, new ConsoleLogger($output));
        $config = (new TestPlanConfigLoader())($configFilePath);
        $testPlan->execute($config);

        return 1;
    }
}
