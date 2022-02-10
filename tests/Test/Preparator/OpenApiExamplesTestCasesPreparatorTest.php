<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Definition\ResponseExample;
use OpenAPITesting\Preparator\OpenApiExamplesTestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

/**
 * @internal
 * @coversDefaultClass
 */
final class OpenApiExamplesTestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getExpectedTestSuites
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new OpenApiExamplesTestCasesPreparator();

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api->getOperations()),
            ['size', 'id', 'headerNames']
        );
    }

    /**
     * @return iterable<string, array{Api, array<TestCase>}>
     */
    public function getExpectedTestSuites(): iterable
    {
        yield 'with 1 query param' => [
            Api::create()->addOperation(
                Operation::create(
                    'test',
                    '/test'
                )
                    ->setMethod('GET')
                    ->addQueryParameter(
                        (new Parameter(
                            'foo',
                            true,
                            new Schema([
                                'type' => 'string',
                            ])
                        ))->addExample(new ParameterExample('200.default', 'bar'))
                    )
                    ->addResponse(
                        DefinitionResponse::create()
                            ->setStatusCode(200)
                            ->addExample(new ResponseExample('200.default'))
                    )
            ),
            [
                new TestCase(
                    '200.default',
                    new Request(
                        'GET',
                        new Uri('/test?foo=bar'),
                    ),
                    new Response(
                        200
                    ),
                ),
            ],
        ];

//                new TestCase(
//                    'default.badRequest',
//                    new Request(
//                        'GET',
//                        new Uri('/pets?1=1&kind=horse&limit=aaa'),
//                    ),
//                    new Response(
//                        400,
//                        [
//                            'content-type' => [
//                                'application/json',
//                            ],
//                        ],
//                        Json::encode([
//                            'code' => 400,
//                            'message' => 'Bad request',
//                        ])
//                    ),
//                    ['listPets', 'get', 'pets', 'preparator_examples'],
//                ),
//                new TestCase(
//                    '200.double',
//                    new Request(
//                        'GET',
//                        new Uri('/pets?1=1&limit=20'),
//                    ),
//                    new Response(
//                        200,
//                        [
//                            'content-type' => [
//                                'application/json',
//                            ],
//                            'x-next' => [
//                                '/toto',
//                            ],
//                        ],
//                        Json::encode(
//                            [
//                                [
//                                    'id' => 12,
//                                    'name' => 'aaa',
//                                ],
//                                [
//                                    'id' => 34,
//                                    'name' => 'bbb',
//                                ],
//                                [
//                                    'id' => 56,
//                                    'name' => 'ccc',
//                                ],
//                                [
//                                    'id' => 78,
//                                    'name' => 'ddd',
//                                ],
//                            ]
//                        ),
//                    ),
//                    ['listPets', 'get', 'pets', 'preparator_examples'],
//                ),
//                new TestCase(
//                    '201',
//                    new Request(
//                        'POST',
//                        new Uri('/pets?1=1'),
//                        [
//                            'content-type' => [
//                                'application/json',
//                            ],
//                        ],
//                        Json::encode([
//                            'id' => 10,
//                            'name' => 'Jessica Smith',
//                        ]),
//                    ),
//                    new Response(
//                        201,
//                        [
//                            'content-type' => [
//                                'application/json',
//                            ],
//                        ],
//                        Json::encode([
//                            'id' => 10,
//                            'name' => 'Jessica Smith',
//                        ]),
//                    ),
//                    ['createPets', 'post', 'pets', 'preparator_examples'],
//                ),
//                new TestCase(
//                    'default.badRequest',
//                    new Request(
//                        'POST',
//                        new Uri('/pets?1=1'),
//                        [
//                            'content-type' => [
//                                'application/json',
//                            ],
//                        ],
//                        Json::encode([
//                            'id' => 11,
//                            'name' => 123,
//                        ])
//                    ),
//                    new Response(
//                        400,
//                        [
//                            'content-type' => [
//                                'application/json',
//                            ],
//                        ],
//                        Json::encode([
//                            'code' => 400,
//                            'message' => 'Bad request',
//                        ])
//                    ),
//                    ['createPets', 'post', 'pets', 'preparator_examples'],
//                ),
//                new TestCase(
//                    '200',
//                    new Request(
//                        'GET',
//                        new Uri('/pets/123?1=1')
//                    ),
//                    new Response(
//                        200,
//                        [
//                            'content-type' => [
//                                'application/json',
//                            ],
//                        ],
//                        Json::encode([
//                            'id' => 10,
//                            'name' => 'Jessica Smith',
//                        ])
//                    ),
//                    ['showPetById', 'get', 'pets', 'preparator_examples'],
//                ),
//            ],
//        ];
    }
}
