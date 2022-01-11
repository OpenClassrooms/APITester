<?php

declare(strict_types=1);

namespace OpenAPITesting\Command;

use OpenAPITesting\Authenticator\OAuth2ImplicitAuthenticator;
use OpenAPITesting\Authenticator\OAuth2PasswordAuthenticator;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Preparator\ErrorsTestCasesPreparator;
use OpenAPITesting\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Requester\HttpAsyncRequester;
use OpenAPITesting\Test\Plan;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class ExecutePlanCommand extends Command
{
    protected static $defaultName = 'launch';

    /**
     * @var \OpenAPITesting\Preparator\TestCasesPreparator[]
     */
    private array $preparators;

    /**
     * @var \OpenAPITesting\Requester\HttpAsyncRequester[]
     */
    private array $requesters;

    /**
     * @var \OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader[]
     */
    private array $loaders;

    /**
     * @var \OpenAPITesting\Authenticator\Authenticator[]
     */
    private array $authenticators;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->preparators = [
            new OpenApiExamplesTestCasesPreparator(),
            new ErrorsTestCasesPreparator(),
        ];
        $this->requesters = [
            new HttpAsyncRequester(),
        ];
        $this->loaders = [
            new OpenApiDefinitionLoader(),
        ];
        $this->authenticators = [
            new OAuth2ImplicitAuthenticator(),
            new OAuth2PasswordAuthenticator(),
        ];
    }

    /**
     * @throws \OpenAPITesting\Authenticator\Exception\AuthenticatorNotFoundException
     * @throws \OpenAPITesting\Definition\Loader\Exception\DefinitionLoaderNotFoundException
     * @throws \OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException
     * @throws \OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException
     * @throws \OpenAPITesting\Preparator\Exception\PreparatorLoadingException
     * @throws \OpenAPITesting\Requester\Exception\RequesterNotFoundException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException
     * @throws \OpenAPITesting\Config\Exception\ConfigurationException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $configFilePath */
        $configFilePath = $input->getOption('config');
        $testPlan = new Plan(
            $this->preparators,
            $this->requesters,
            $this->loaders,
            $this->authenticators,
            new ConsoleLogger($output),
        );
        $config = new PlanConfig($configFilePath);
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
