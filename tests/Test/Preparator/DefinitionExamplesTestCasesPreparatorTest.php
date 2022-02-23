<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Definition\Request as DefinitionRequest;
use OpenAPITesting\Definition\RequestExample;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Definition\ResponseExample;
use OpenAPITesting\Preparator\DefinitionExamplesTestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;

/**
 * @internal
 * @coversDefaultClass
 */
final class DefinitionExamplesTestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getExpectedTestSuites
     *
     * @param TestCase[] $expected
     */
    public function testPrepare(Api $api, array $expected): void
    {
        $preparator = new DefinitionExamplesTestCasesPreparator();

        $preparator->configure([
            'fixturesPath' => null,
        ]);
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
                        DefinitionResponse::create(200)
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

        yield 'with multiple expected responses' => [
            Api::create()->addOperation(
                Operation::create(
                    'test',
                    '/test'
                )
                    ->addQueryParameter(
                        (new Parameter(
                            'foo',
                            true,
                            new Schema([
                                'type' => 'string',
                            ])
                        ))->addExample(new ParameterExample('200.default', 'bar'))
                            ->addExample(new ParameterExample('400', '1234'))
                    )
                    ->addResponse(
                        DefinitionResponse::create(200)
                            ->addExample(new ResponseExample('200.default'))
                    )
                    ->addResponse(
                        DefinitionResponse::create(400)
                            ->addExample(
                                new ResponseExample('400', [
                                    'message' => 'Bad request',
                                ])
                            )
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
                new TestCase(
                    '400',
                    new Request(
                        'GET',
                        new Uri('/test?foo=1234'),
                    ),
                    new Response(
                        400,
                        [],
                        Json::encode([
                            'message' => 'Bad request',
                        ])
                    ),
                ),
            ],
        ];

        yield 'with request body' => [
            Api::create()->addOperation(
                Operation::create(
                    'test',
                    '/test',
                    'POST'
                )
                    ->addRequest(
                        DefinitionRequest::create(
                            'application/json',
                            new Schema([
                                'type' => 'object',
                                'properties' => [
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                    'age' => [
                                        'type' => 'integer',
                                    ],
                                ],
                            ])
                        )->addExample(
                            new RequestExample('201', [
                                'name' => 'John Doe',
                                'age' => 25,
                            ])
                        )
                            ->addExample(
                                new RequestExample('400.missingParameter', [
                                    'name' => 'John Doe',
                                ])
                            )
                    )
                    ->addResponse(
                        DefinitionResponse::create(201)
                            ->addExample(new ResponseExample('201'))
                    )
                    ->addResponse(
                        DefinitionResponse::create(400)
                            ->addExample(
                                new ResponseExample('400.missingParameter', [
                                    'message' => 'Missing parameter',
                                ])
                            )
                            ->addExample(
                                new ResponseExample('400.other', [
                                    'message' => 'other error message',
                                ])
                            )
                    )
            ),
            [
                new TestCase(
                    '201',
                    new Request(
                        'POST',
                        new Uri('/test'),
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'name' => 'John Doe',
                            'age' => 25,
                        ])
                    ),
                    new Response(201),
                ),
                new TestCase(
                    '400.missingParameter',
                    new Request(
                        'POST',
                        new Uri('/test'),
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'name' => 'John Doe',
                        ])
                    ),
                    new Response(
                        400,
                        [],
                        Json::encode([
                            'message' => 'Missing parameter',
                        ])
                    ),
                ),
            ],
        ];

        yield 'with path parameters' => [
            Api::create()->addOperation(
                Operation::create(
                    'test',
                    '/user/{userId}/comment/{commentId}',
                    'GET'
                )
                    ->addPathParameter(
                        Parameter::create('userId', true)->setSchema(
                            new Schema([
                                'type' => 'integer',
                            ])
                        )
                            ->addExample(new ParameterExample('200', '123'))
                            ->addExample(new ParameterExample('400.default', 'foo'))
                    )
                    ->addPathParameter(
                        Parameter::create('commentId', true)->setSchema(
                            new Schema([
                                'type' => 'integer',
                            ])
                        )
                            ->addExample(new ParameterExample('200', '456'))
                            ->addExample(new ParameterExample('400.default', 'bar'))
                    )
                    ->addResponse(
                        DefinitionResponse::create(200)
                            ->addExample(
                                new ResponseExample('200', [
                                    'title' => 'foo',
                                    'content' => 'bar',
                                ])
                            )
                    )
                    ->addResponse(
                        DefinitionResponse::create(400)
                            ->addExample(
                                new ResponseExample('400.default', [
                                    'message' => 'Bad request',
                                ])
                            )
                            ->addExample(
                                new ResponseExample('400.other', [
                                    'message' => 'other error message',
                                ])
                            )
                    )
            ),
            [
                new TestCase(
                    '200',
                    new Request(
                        'GET',
                        new Uri('/user/123/comment/456'),
                    ),
                    new Response(
                        200,
                        [],
                        Json::encode([
                            'title' => 'foo',
                            'content' => 'bar',
                        ])
                    ),
                ),
                new TestCase(
                    '400.default',
                    new Request(
                        'GET',
                        new Uri('/user/foo/comment/bar'),
                    ),
                    new Response(
                        400,
                        [],
                        Json::encode([
                            'message' => 'Bad request',
                        ])
                    ),
                ),
            ],
        ];

        yield 'with headers in request and response' => [
            Api::create()->addOperation(
                Operation::create(
                    'test',
                    '/test',
                    'GET'
                )
                    ->addHeader(
                        Parameter::create('x-next', true)->setSchema(
                            new Schema([
                                'type' => 'string',
                            ])
                        )
                            ->addExample(new ParameterExample('200', '/test?offset=20&limit=20'))
                            ->addExample(new ParameterExample('400', '123'))
                    )
                    ->addResponse(
                        DefinitionResponse::create(200)
                            ->addExample(
                                new ResponseExample('200', [
                                    'title' => 'foo',
                                    'content' => 'bar',
                                ])
                            )
                            ->setHeaders(
                                new Parameters([
                                    Parameter::create('x-foo')->addExample(new ParameterExample('200', 'bar')),
                                ])
                            )
                    )
                    ->addResponse(
                        DefinitionResponse::create(400)
                            ->addExample(
                                new ResponseExample('400', [
                                    'message' => 'Bad request',
                                ])
                            )
                    )
            ),
            [
                new TestCase(
                    '200',
                    new Request(
                        'GET',
                        new Uri('/test'),
                        [
                            'x-next' => '/test?offset=20&limit=20',
                        ]
                    ),
                    new Response(
                        200,
                        [
                            'x-foo' => 'bar',
                        ],
                        Json::encode([
                            'title' => 'foo',
                            'content' => 'bar',
                        ])
                    ),
                ),
                new TestCase(
                    '400',
                    new Request(
                        'GET',
                        new Uri('/test'),
                        [
                            'x-next' => '123',
                        ]
                    ),
                    new Response(
                        400,
                        [],
                        Json::encode([
                            'message' => 'Bad request',
                        ])
                    ),
                ),
            ],
        ];
    }
}
