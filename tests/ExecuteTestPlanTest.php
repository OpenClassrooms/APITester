<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests;

use OpenAPITesting\Loader\AggregateLoader;
use OpenAPITesting\Loader\FileConcatLoader;
use OpenAPITesting\Loader\Fixture\AliceFixtureLoader;
use OpenAPITesting\Loader\JsonLoader;
use OpenAPITesting\Loader\OpenApiLoader;
use OpenAPITesting\Loader\YamlLoader;
use OpenAPITesting\Requester\HttpRequester;
use OpenAPITesting\Test\TestSuite;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;
use function Psl\Json\encode;

/**
 * @internal
 * @coversNothing
 */
final class ExecuteTestPlanTest extends TestCase
{
    public function testExecute(): void
    {
        $openApiLoader = new AggregateLoader(new FileConcatLoader(), new JsonLoader(), new OpenApiLoader());
        $fixtureLoader = new AggregateLoader(new FileConcatLoader(), new YamlLoader(), new AliceFixtureLoader());
        $testPlan = new TestSuite(
            $openApiLoader->load(FixturesLocation::OPEN_API_PETSTORE_FILE),
            $fixtureLoader->load(FixturesLocation::FIXTURE_OPERATION_TEST_SUITE_1)
        );
        $testPlan->launch(new HttpRequester());
        static::assertEmpty($testPlan->getErrors(), encode($testPlan->getErrors(), true));
    }
}
