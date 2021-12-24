<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests;

use OpenAPITesting\Config\DefinitionConfig;
use OpenAPITesting\Config\TestPlanConfig;
use OpenAPITesting\Config\TestSuiteConfig;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Test\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Test\TestPlan;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use OpenAPITesting\Util\Json;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class TestPlanTest extends TestCase
{
    public function testExecute(): void
    {
        $testPlan = new TestPlan(
            [new OpenApiExamplesTestCasesPreparator()],
            [new OpenApiDefinitionLoader()],
        );
        $testPlan->execute(
            new TestPlanConfig(
                [
                    new TestSuiteConfig(
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
