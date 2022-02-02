<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test;

use OpenAPITesting\Authenticator\OAuth2ImplicitAuthenticator;
use OpenAPITesting\Authenticator\OAuth2PasswordAuthenticator;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Preparator\Error401TestCasesPreparator;
use OpenAPITesting\Preparator\Error403TestCasesPreparator;
use OpenAPITesting\Preparator\Error404TestCasesPreparator;
use OpenAPITesting\Preparator\Error405TestCasesPreparator;
use OpenAPITesting\Preparator\Error406TestCasesPreparator;
use OpenAPITesting\Preparator\Error413TestCasesPreparator;
use OpenAPITesting\Preparator\Error416TestCasesPreparator;
use OpenAPITesting\Preparator\FixturesTestCasesPreparator;
use OpenAPITesting\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Requester\HttpAsyncRequester;
use OpenAPITesting\Test\Plan;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 * @group integration
 * @coversDefaultClass
 */
final class PlanTest extends TestCase
{
    private Plan $testPlan;

    protected function setUp(): void
    {
        $this->testPlan = new Plan(
            [
                new Error401TestCasesPreparator(),
                new Error403TestCasesPreparator(),
                new Error404TestCasesPreparator(),
                new Error405TestCasesPreparator(),
                new Error406TestCasesPreparator(),
                new Error413TestCasesPreparator(),
                new Error416TestCasesPreparator(),
                new OpenApiExamplesTestCasesPreparator(),
                new FixturesTestCasesPreparator(),
            ],
            [new HttpAsyncRequester()],
            [new OpenApiDefinitionLoader()],
            [new OAuth2PasswordAuthenticator(), new OAuth2ImplicitAuthenticator()],
            new ConsoleLogger(new ConsoleOutput(OutputInterface::VERBOSITY_VERY_VERBOSE)),
        );
    }

    public function testPetStore(): void
    {
        $config = new PlanConfig(FixturesLocation::CONFIG_OPENAPI);
        $this->testPlan->execute($config, 'petstore');
        $this->testPlan->assert();
    }
}
