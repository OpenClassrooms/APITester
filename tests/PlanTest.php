<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests;

use OpenAPITesting\Authenticator\OAuth2ImplicitAuthenticator;
use OpenAPITesting\Authenticator\OAuth2PasswordAuthenticator;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Preparator\ErrorsTestCasesPreparator;
use OpenAPITesting\Preparator\FixturesTestCasesPreparator;
use OpenAPITesting\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Requester\HttpAsyncRequester;
use OpenAPITesting\Test\Plan;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;

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
                new OpenApiExamplesTestCasesPreparator(),
                new ErrorsTestCasesPreparator(),
                new FixturesTestCasesPreparator(),
            ],
            [new HttpAsyncRequester()],
            [new OpenApiDefinitionLoader()],
            [new OAuth2PasswordAuthenticator(), new OAuth2ImplicitAuthenticator()]
        );
    }

    public function testExecute(): void
    {
        $config = new PlanConfig(FixturesLocation::CONFIG_OPENAPI);
        $this->testPlan->execute($config);
        $this->testPlan->assert();
    }
}
