<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Collection\Parameters;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\ParameterExample;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Definition\ResponseExample;
use APITester\Preparator\Config\DefinitionExamples;
use APITester\Preparator\DefinitionExamplesTestCasesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use APITester\Util\Json;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;

/**
 * @internal
 * @coversDefaultClass
 */
final class DefinitionExamplesTestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigureConfig(): void
    {
        $preparator = new DefinitionExamplesTestCasesPreparator();
        $preparator->configure([
            'extensionPath' => '/foo/bar/',
        ]);
        /** @var DefinitionExamples $config */
        $config = $preparator->getConfig();
        static::assertSame('/foo/bar/', $config->extensionPath);
    }

    /**
     * @dataProvider getExpectedTestSuites
     *
     * @param TestCase[] $expected
     */
    public function testPrepare(Api $api, array $expected): void
    {
        $preparator = new DefinitionExamplesTestCasesPreparator();

        $preparator->configure([]);
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
//        yield 'with 1 query param' => [
//            Api::create()->addOperation(
//                Operation::create(
//                    'test',
//                    '/test'
//                )
//                    ->addQueryParameter(
//                        (new Parameter(
//                            'foo',
//                            true,
//                            new Schema([
//                                'type' => 'string',
//                            ])
//                        ))->addExample(new ParameterExample('200.default', 'bar'))
//                    )
//                    ->addResponse(
//                        DefinitionResponse::create(200)
//                            ->addExample(new ResponseExample('200.default'))
//                    )
//            ),
//            [
//                new TestCase(
//                    'operation: test example: 200.default',
//                    new Request(
//                        'GET',
//                        new Uri('/test?foo=bar'),
//                    ),
//                    new Response(
//                        200
//                    ),
//                ),
//            ],
//        ];
//
//        yield 'with multiple expected responses' => [
//            Api::create()->addOperation(
//                Operation::create(
//                    'test',
//                    '/test'
//                )
//                    ->addQueryParameter(
//                        (new Parameter(
//                            'foo',
//                            true,
//                            new Schema([
//                                'type' => 'string',
//                            ])
//                        ))->addExample(new ParameterExample('200.default', 'bar'))
//                            ->addExample(new ParameterExample('400', '1234'))
//                    )
//                    ->addResponse(
//                        DefinitionResponse::create(200)
//                            ->addExample(new ResponseExample('200.default'))
//                    )
//                    ->addResponse(
//                        DefinitionResponse::create(400)
//                            ->addExample(
//                                new ResponseExample('400', [
//                                    'message' => 'Bad request',
//                                ])
//                            )
//                    )
//            ),
//            [
//                new TestCase(
//                    'operation: test example: 200.default',
//                    new Request(
//                        'GET',
//                        new Uri('/test?foo=bar'),
//                    ),
//                    new Response(
//                        200
//                    ),
//                ),
//                new TestCase(
//                    'operation: test example: 400',
//                    new Request(
//                        'GET',
//                        new Uri('/test?foo=1234'),
//                    ),
//                    new Response(
//                        400,
//                        [],
//                        Json::encode([
//                            'message' => 'Bad request',
//                        ])
//                    ),
//                ),
//            ],
//        ];
//
//        yield 'with request body' => [
//            Api::create()->addOperation(
//                Operation::create(
//                    'test',
//                    '/test',
//                    'POST'
//                )
//                    ->addRequest(
//                        DefinitionRequest::create(
//                            'application/json',
//                            new Schema([
//                                'type' => 'object',
//                                'properties' => [
//                                    'name' => [
//                                        'type' => 'string',
//                                    ],
//                                    'age' => [
//                                        'type' => 'integer',
//                                    ],
//                                ],
//                            ])
//                        )->addExample(
//                            new RequestExample('201', [
//                                'name' => 'John Doe',
//                                'age' => 25,
//                            ])
//                        )
//                            ->addExample(
//                                new RequestExample('400.missingParameter', [
//                                    'name' => 'John Doe',
//                                ])
//                            )
//                    )
//                    ->addResponse(
//                        DefinitionResponse::create(201)
//                            ->addExample(new ResponseExample('201'))
//                    )
//                    ->addResponse(
//                        DefinitionResponse::create(400)
//                            ->addExample(
//                                new ResponseExample('400.missingParameter', [
//                                    'message' => 'Missing parameter',
//                                ])
//                            )
//                            ->addExample(
//                                new ResponseExample('400.other', [
//                                    'message' => 'other error message',
//                                ])
//                            )
//                    )
//            ),
//            [
//                new TestCase(
//                    'operation: test example: 201',
//                    new Request(
//                        'POST',
//                        new Uri('/test'),
//                        [
//                            'content-type' => [
//                                'application/json',
//                            ],
//                        ],
//                        Json::encode([
//                            'name' => 'John Doe',
//                            'age' => 25,
//                        ])
//                    ),
//                    new Response(201),
//                ),
//                new TestCase(
//                    'operation: test example: 400.missingParameter',
//                    new Request(
//                        'POST',
//                        new Uri('/test'),
//                        [
//                            'content-type' => [
//                                'application/json',
//                            ],
//                        ],
//                        Json::encode([
//                            'name' => 'John Doe',
//                        ])
//                    ),
//                    new Response(
//                        400,
//                        [],
//                        Json::encode([
//                            'message' => 'Missing parameter',
//                        ])
//                    ),
//                ),
//            ],
//        ];
//
//        yield 'with path parameters' => [
//            Api::create()->addOperation(
//                Operation::create(
//                    'test',
//                    '/user/{userId}/comment/{commentId}',
//                    'GET'
//                )
//                    ->addPathParameter(
//                        Parameter::create('userId', true)->setSchema(
//                            new Schema([
//                                'type' => 'integer',
//                            ])
//                        )
//                            ->addExample(new ParameterExample('200', '123'))
//                            ->addExample(new ParameterExample('400.default', 'foo'))
//                    )
//                    ->addPathParameter(
//                        Parameter::create('commentId', true)->setSchema(
//                            new Schema([
//                                'type' => 'integer',
//                            ])
//                        )
//                            ->addExample(new ParameterExample('200', '456'))
//                            ->addExample(new ParameterExample('400.default', 'bar'))
//                    )
//                    ->addResponse(
//                        DefinitionResponse::create(200)
//                            ->addExample(
//                                new ResponseExample('200', [
//                                    'title' => 'foo',
//                                    'content' => 'bar',
//                                ])
//                            )
//                    )
//                    ->addResponse(
//                        DefinitionResponse::create(400)
//                            ->addExample(
//                                new ResponseExample('400.default', [
//                                    'message' => 'Bad request',
//                                ])
//                            )
//                            ->addExample(
//                                new ResponseExample('400.other', [
//                                    'message' => 'other error message',
//                                ])
//                            )
//                    )
//            ),
//            [
//                new TestCase(
//                    'operation: test example: 200',
//                    new Request(
//                        'GET',
//                        new Uri('/user/123/comment/456'),
//                    ),
//                    new Response(
//                        200,
//                        [],
//                        Json::encode([
//                            'title' => 'foo',
//                            'content' => 'bar',
//                        ])
//                    ),
//                ),
//                new TestCase(
//                    'operation: test example: 400.default',
//                    new Request(
//                        'GET',
//                        new Uri('/user/foo/comment/bar'),
//                    ),
//                    new Response(
//                        400,
//                        [],
//                        Json::encode([
//                            'message' => 'Bad request',
//                        ])
//                    ),
//                ),
//            ],
//        ];

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
