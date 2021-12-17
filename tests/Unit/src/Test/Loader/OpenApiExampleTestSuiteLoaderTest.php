<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Unit\src\Test\Loader;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Loader\OpenApiLoader;
use OpenAPITesting\Test\Loader\OpenApiExampleTestSuiteLoader;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Test\TestSuite;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;

/**
 * @internal
 * @covers \OpenAPITesting\Test\Loader\OpenApiExampleTestSuiteLoader
 */
final class OpenApiExampleTestSuiteLoaderTest extends \PHPUnit\Framework\TestCase
{
    public const OPENAPI_LOCATION = __DIR__ . '/../../../fixtures/openapi.yaml';

    /**
     * @dataProvider getExpectedTestSuites
     *
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
     * @throws \cebe\openapi\exceptions\IOException
     * @throws \cebe\openapi\exceptions\TypeErrorException
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function test(TestSuite $expected): void
    {
        $openApi = (new OpenApiLoader())(self::OPENAPI_LOCATION);
        $testSuite = (new OpenApiExampleTestSuiteLoader())($openApi);

        Assert::assertObjectsEqual(
            $expected,
            $testSuite,
            ['size']
        );
    }

    /**
     * @return iterable<array-key, TestSuite[]>
     */
    public function getExpectedTestSuites(): iterable
    {
        yield [
            new TestSuite([
                new TestCase(
                    new Request(
                        'GET',
                        new Uri('/pets?1=1&kind=cat&limit=10'),
                    ),
                    new Response(
                        200,
                    ),
                    ['listPets'],
                    '200.default',
                ),
                new TestCase(
                    new Request(
                        'GET',
                        new Uri('/pets?1=1&kind=horse&limit=aaa'),
                    ),
                    new Response(
                        400, // @todo: fix bad response mapping
                    ),
                    ['listPets'],
                    'default.badRequest',
                ),
                new TestCase(
                    new Request(
                        'GET',
                        new Uri('/pets?1=1&limit=20'),
                    ),
                    new Response(
                        200,
                    ),
                    ['listPets'],
                    '200.double',
                ),
                new TestCase(
                    new Request(
                        'POST',
                        new Uri('/pets?1=1'),
                        [],
                        Json::encode([
                            'id' => 10,
                            'name' => 'Jessica Smith',
                        ])
                    ),
                    new Response(
                        201,
                    ),
                    ['createPets'],
                    '201',
                ),
                new TestCase(
                    new Request(
                        'POST',
                        new Uri('/pets?1=1'),
                        [],
                        Json::encode([
                            'id' => 11,
                            'name' => 123,
                        ])
                    ),
                    new Response(
                        400,
                    ),
                    ['createPets'],
                    'default.badRequest'
                ),
                new TestCase(
                    new Request(
                        'GET',
                        new Uri('/pets/123?1=1'),
                        [],
                        Json::encode([
                            'id' => 11,
                            'name' => 123,
                        ])
                    ),
                    new Response(
                        200,
                        [],
                        Json::encode([
                            'id' => 10,
                            'name' => 'Jessica Smith',
                        ])
                    ),
                    ['showPetById'],
                    '200.200'
                ),
            ]),
        ];
    }
}
