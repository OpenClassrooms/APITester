<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests;

use OpenAPITesting\Loader\Fixture\OpenApiExampleFixtureLoader;
use OpenAPITesting\Loader\OpenApiLoader;
use OpenAPITesting\Requester\HttpRequester;
use OpenAPITesting\Test\TestSuite;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use OpenAPITesting\Util\Json;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ExecuteTestPlanTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException
     * @throws \cebe\openapi\exceptions\TypeErrorException
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \InvalidArgumentException
     */
    public function testExecute(): void
    {
        $openApi = (new OpenApiLoader())(FixturesLocation::OPEN_API_PETSTORE_YAML);
        $testSuite = new TestSuite(
            new HttpRequester(rtrim($openApi->servers[0]->url, '/')),
            (new OpenApiExampleFixtureLoader())($openApi),
        );

        $testSuite->launch();

        static::assertEmpty($testSuite->getErrors(), Json::encode($testSuite->getErrors()));
    }
}
