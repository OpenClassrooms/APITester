<?php

namespace OpenAPITesting\Tests;

use OpenAPITesting\Requester\HttpRequester;
use OpenAPITesting\Loader\AggregateLoader;
use OpenAPITesting\Loader\AliceFixtureLoader;
use OpenAPITesting\Loader\FileConcatLoader;
use OpenAPITesting\Loader\JsonLoader;
use OpenAPITesting\Loader\OpenApiLoader;
use OpenAPITesting\Loader\YamlLoader;
use OpenAPITesting\Test\TestPlan;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;

class ExecuteTestPlanTest extends TestCase
{
    /**
     * @test
     * @throws \JsonException
     */
    public function execute(): void
    {
        $openApiLoader = new AggregateLoader(new FileConcatLoader(), new JsonLoader(), new OpenApiLoader());
        $fixtureLoader = new AggregateLoader(new FileConcatLoader(), new YamlLoader(), new AliceFixtureLoader());
        $testPlan = new TestPlan(
            $openApiLoader->load(FixturesLocation::OPEN_API_PETSTORE_FILE),
            $fixtureLoader->load(FixturesLocation::FIXTURE_OPERATION_TEST_SUITE_1)
        );
        $testPlan->launch(new HttpRequester());
        $this->assertEmpty($testPlan->getErrors(), json_encode($testPlan->getErrors(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }
}
