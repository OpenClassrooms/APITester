<?php

declare(strict_types=1);

namespace OpenAPITesting\Command;

use OpenAPITesting\Authenticator\Authenticator;
use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Authenticator\Exception\AuthenticatorNotFoundException;
use OpenAPITesting\Authenticator\OAuth2ImplicitAuthenticator;
use OpenAPITesting\Authenticator\OAuth2PasswordAuthenticator;
use OpenAPITesting\Config\Exception\ConfigurationException;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoaderNotFoundException;
use OpenAPITesting\Definition\Loader\Exception\DefinitionLoadingException;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Preparator\Error401TestCasesPreparator;
use OpenAPITesting\Preparator\Error404TestCasesPreparator;
use OpenAPITesting\Preparator\Error405TestCasesPreparator;
use OpenAPITesting\Preparator\Error406TestCasesPreparator;
use OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Preparator\TestCasesPreparator;
use OpenAPITesting\Requester\Exception\RequesterNotFoundException;
use OpenAPITesting\Requester\HttpAsyncRequester;
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
     * @var TestCasesPreparator[]
     */
    private array $preparators;

    /**
     * @var HttpAsyncRequester[]
     */
    private array $requesters;

    /**
     * @var OpenApiDefinitionLoader[]
     */
    private array $loaders;

    /**
     * @var Authenticator[]
     */
    private array $authenticators;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->preparators = [
            new OpenApiExamplesTestCasesPreparator(),
            new Error401TestCasesPreparator(),
            new Error404TestCasesPreparator(),
            new Error405TestCasesPreparator(),
            new Error406TestCasesPreparator(),
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
     * @throws AuthenticatorNotFoundException
     * @throws DefinitionLoaderNotFoundException
     * @throws DefinitionLoadingException
     * @throws InvalidPreparatorConfigException
     * @throws PreparatorLoadingException
     * @throws RequesterNotFoundException
     * @throws ClientExceptionInterface
     * @throws AuthenticationLoadingException
     * @throws ConfigurationException
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
