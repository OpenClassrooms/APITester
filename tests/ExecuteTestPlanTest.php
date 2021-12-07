<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests;

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
    /**
     * @throws \JsonException
     * @throws \cebe\openapi\exceptions\TypeErrorException
     * @throws \Nelmio\Alice\Throwable\LoadingThrowable
     */
    public function testExecute(): void
    {
        $jsonLoader = new JsonLoader();
        $yamlLoader = new YamlLoader();

        $openApiLoader = new OpenApiLoader();

        $aliceFixtureLoader = new AliceFixtureLoader();

        $testPlan = new TestSuite(
            $openApiLoader($jsonLoader(FixturesLocation::OPEN_API_PETSTORE_FILE)),
            $aliceFixtureLoader($yamlLoader(FixturesLocation::FIXTURE_OPERATION_TEST_SUITE_1)),
        );
        $testPlan->launch(new HttpRequester());
        static::assertEmpty($testPlan->getErrors(), encode($testPlan->getErrors(), true));
    }
}
