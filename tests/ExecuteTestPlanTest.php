<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests;

use cebe\openapi\Reader;
use OpenAPITesting\Loader\Fixture\OpenApiExampleFixtureLoader;
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
//        $jsonLoader = new JsonLoader();

//        $openApiLoader = new OpenApiLoader();

//        $aliceFixtureLoader = new AliceFixtureLoader();
        $openApiFixtureLoader = new OpenApiExampleFixtureLoader();

//        $openApiPsx = OpenAPI::fromFile(FixturesLocation::OPEN_API_PETSTORE_YAML, '/pets');
//        dd($openApiPsx);
        $openApi = Reader::readFromYamlFile(realpath(FixturesLocation::OPEN_API_PETSTORE_YAML));
        $testPlan = new TestSuite(
            rtrim($openApi->servers[0]->url, '/'),
            $openApiFixtureLoader($openApi),
        // $aliceFixtureLoader($yamlLoader(FixturesLocation::FIXTURE_OPERATION_TEST_SUITE_1)),
        );

        $testPlan->launch(new HttpRequester());

        static::assertEmpty($testPlan->getErrors(), encode($testPlan->getErrors(), true));
    }
}
