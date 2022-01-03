<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests;

use OpenAPITesting\Config\DefinitionConfig;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Config\SuiteConfig;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Requester\HttpAsyncRequester;
use OpenAPITesting\Test\Plan;
use OpenAPITesting\Test\Preparator\OpenApiExamplesTestCasesPreparator;
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
    public function testExecute(): void
    {
        $testPlan = new Plan(
            [
                new OpenApiExamplesTestCasesPreparator(),
                new Status404TestCasesPreparator(),
            ],
            [new HttpAsyncRequester()],
            [new OpenApiDefinitionLoader()],
        );
        $testPlan->execute(
            new PlanConfig(
                [
                    (new SuiteConfig(
                        'test',
                        new DefinitionConfig(
                            FixturesLocation::OPEN_API_PETSTORE_YAML,
                            'openapi'
                        ),
                    ))->exclude([
                        'getUserByName',
                    ]),
                ],
            ),
        );
        $testPlan->assert();
    }
}
