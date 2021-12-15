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

        $openApi = Reader::readFromYamlFile(realpath(FixturesLocation::OPEN_API_PETSTORE_YAML));
        $testSuite = new TestSuite(
            rtrim($openApi->servers[0]->url, '/'),
            $openApiFixtureLoader($openApi),
        // $aliceFixtureLoader($yamlLoader(FixturesLocation::FIXTURE_OPERATION_TEST_SUITE_1)),
        );

        $testSuite->launch(new HttpRequester());

        static::assertEmpty($testSuite->getErrors(), encode($testSuite->getErrors(), true));
    }
}
