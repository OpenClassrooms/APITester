<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Loader\OpenApiDefinitionLoader;
use OpenAPITesting\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;

/**
 * @internal
 * @coversDefaultClass
 */
final class OpenApiExamplesTestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    private const OPENAPI_LOCATION = __DIR__ . '/../../Fixtures/OpenAPI/openapi-with-examples.yaml';

    /**
     * @dataProvider getExpectedTestSuites
     *
     * @param TestCase[] $expected
     */
    public function test(array $expected): void
    {
        $api = (new OpenApiDefinitionLoader())->load(self::OPENAPI_LOCATION);
        $preparator = new OpenApiExamplesTestCasesPreparator();

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api->getOperations()),
            ['size', 'id', 'headerNames']
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
                    '200.default',
                    new Request(
                        'GET',
                        new Uri('/pets?1=1&kind=cat&limit=10'),
                    ),
                    new Response(
                        200,
                        [
                            'content-type' => [
                                'application/json',
                            ],
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
                    ['listPets', 'get', 'pets', 'preparator_examples'],
                ),
                new TestCase(
                    'default.badRequest',
                    new Request(
                        'GET',
                        new Uri('/pets?1=1&kind=horse&limit=aaa'),
                    ),
                    new Response(
                        400,
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'code' => 400,
                            'message' => 'Bad request',
                        ])
                    ),
                    ['listPets', 'get', 'pets', 'preparator_examples'],
                ),
                new TestCase(
                    '200.double',
                    new Request(
                        'GET',
                        new Uri('/pets?1=1&limit=20'),
                    ),
                    new Response(
                        200,
                        [
                            'content-type' => [
                                'application/json',
                            ],
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
                    ['listPets', 'get', 'pets', 'preparator_examples'],
                ),
                new TestCase(
                    '201',
                    new Request(
                        'POST',
                        new Uri('/pets?1=1'),
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'id' => 10,
                            'name' => 'Jessica Smith',
                        ]),
                    ),
                    new Response(
                        201,
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'id' => 10,
                            'name' => 'Jessica Smith',
                        ]),
                    ),
                    ['createPets', 'post', 'pets', 'preparator_examples'],
                ),
                new TestCase(
                    'default.badRequest',
                    new Request(
                        'POST',
                        new Uri('/pets?1=1'),
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'id' => 11,
                            'name' => 123,
                        ])
                    ),
                    new Response(
                        400,
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'code' => 400,
                            'message' => 'Bad request',
                        ])
                    ),
                    ['createPets', 'post', 'pets', 'preparator_examples'],
                ),
                new TestCase(
                    '200',
                    new Request(
                        'GET',
                        new Uri('/pets/123?1=1')
                    ),
                    new Response(
                        200,
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'id' => 10,
                            'name' => 'Jessica Smith',
                        ])
                    ),
                    ['showPetById', 'get', 'pets', 'preparator_examples'],
                ),
            ],
        ];
    }
}
