<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests;

use OpenAPITesting\Config\Loader\PlanConfigLoader;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Requester\HttpAsyncRequester;
use OpenAPITesting\Test\Plan;
use OpenAPITesting\Test\Preparator\FixturesTestCasesPreparator;
use OpenAPITesting\Test\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Test\Preparator\Status401TestCasesPreparator;
use OpenAPITesting\Test\Preparator\Status404TestCasesPreparator;
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
                new Status404TestCasesPreparator(),
                new Status401TestCasesPreparator(),
                new FixturesTestCasesPreparator(),
            ],
            [new HttpAsyncRequester()],
            [new OpenApiDefinitionLoader()],
        );
    }

    public function testExecute(): void
    {
        $config = (new PlanConfigLoader())(FixturesLocation::CONFIG_OPENAPI);
        $this->testPlan->execute($config);
        $this->testPlan->assert();
    }
}
