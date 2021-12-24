<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Loader;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Test\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;

/**
 * @internal
 * @covers \OpenAPITesting\Test\Preparator\OpenApiExamplesTestCasesPreparator
 */
final class OpenApiExampleTestSuiteLoaderTest extends \PHPUnit\Framework\TestCase
{
    private const OPENAPI_LOCATION = __DIR__ . '/../../Fixtures/OpenAPI/openapi-with-examples.yaml';

    /**
     * @dataProvider getExpectedTestSuites
     *
     * @param TestCase[] $expected
     */
    public function test(array $expected): void
    {
        $openApi = (new OpenApiDefinitionLoader())->load(self::OPENAPI_LOCATION);
        $testSuite = (new OpenApiExamplesTestCasesPreparator())($openApi);

        Assert::assertObjectsEqual(
            $expected,
            $testSuite,
            ['size']
        );
    }

    /**
     * @return iterable<array-key, TestCase[][]>
     */
    public function getExpectedTestSuites(): iterable
    {
        yield [
            [
                new TestCase(
                    new Request(
                        'GET',
                        new Uri('/pets?1=1&kind=cat&limit=10'),
                    ),
                    new Response(
                        200,
                        [
                            'x-next' => [
                                '/toto',
                            ],
                        ],
                        Json::encode([
                            [
                                'id' => 12,
                                'name' => 'aaa',
                            ],
                            [
                                'id' => 34,
                                'name' => 'bbb',
                            ],
                        ]),
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
                        400,
                        [],
                        Json::encode([
                            'code' => 400,
                            'message' => 'Bad request',
                        ])
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
                        [
                            'x-next' => [
                                '/toto',
                            ],
                        ],
                        Json::encode(
                            [
                                [
                                    'id' => 12,
                                    'name' => 'aaa',
                                ],
                                [
                                    'id' => 34,
                                    'name' => 'bbb',
                                ],
                                [
                                    'id' => 56,
                                    'name' => 'ccc',
                                ],
                                [
                                    'id' => 78,
                                    'name' => 'ddd',
                                ],
                            ]
                        ),
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
                        ]),
                    ),
                    new Response(
                        201,
                        [],
                        Json::encode([
                            'id' => 10,
                            'name' => 'Jessica Smith',
                        ]),
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
                        [],
                        Json::encode([
                            'code' => 400,
                            'message' => 'Bad request',
                        ])
                    ),
                    ['createPets'],
                    'default.badRequest',
                ),
                new TestCase(
                    new Request(
                        'GET',
                        new Uri('/pets/123?1=1')
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
                    '200',
                ),
            ],
        ];
    }
}
