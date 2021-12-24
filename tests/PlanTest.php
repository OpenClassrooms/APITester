<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests;

use OpenAPITesting\Config\DefinitionConfig;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Config\SuiteConfig;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Test\Plan;
use OpenAPITesting\Test\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use OpenAPITesting\Util\Json;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class PlanTest extends TestCase
{
    public function testExecute(): void
    {
        $testPlan = new Plan(
            [new OpenApiExamplesTestCasesPreparator()],
            [new OpenApiDefinitionLoader()],
        );
        $testPlan->execute(
            new PlanConfig(
                [
                    new SuiteConfig(
                        'test',
                        new DefinitionConfig(
                            FixturesLocation::OPEN_API_PETSTORE_YAML,
                            'openapi'
                        ),
                        ['examples'],
                        ['get']
                    ),
                ]
            )
        );

        static::assertEmpty($testPlan->getErrors(), Json::encode($testPlan->getErrors()));
    }
}
