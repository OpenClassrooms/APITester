<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Body;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Preparator\Config\ExamplesConfig;
use APITester\Preparator\ExamplesPreparator;
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
final class ExamplesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigureConfig(): void
    {
        $preparator = new ExamplesPreparator();
        $preparator->configure([
            'extensionPath' => '/foo/bar/',
        ]);
        /** @var ExamplesConfig $config */
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
        $preparator = new ExamplesPreparator();

        $preparator->configure([]);
        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations())
        );
    }

    /**
     * @return iterable<string, array{Api, array<TestCase>}>
     */
    public function getExpectedTestSuites(): iterable
    {
        yield 'with 1 query param' => [
            Api::create()->addOperation(
                Operation::create('test', '/test')
                    ->addQueryParameter(
                        Parameter::create('foo')
                            ->setSchema(
                                new Schema([
                                    'type' => 'string',
                                ])
                            )
                    )
                    ->addResponse(DefinitionResponse::create(200))
                    ->addExample(
                        OperationExample::create('200.default')
                            ->setQueryParameters([
                                'foo' => 'bar',
                            ])
                            ->setResponse(new ResponseExample())
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 200.default',
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
                Operation::create('test', '/test')
                    ->addQueryParameter(
                        Parameter::create('foo')
                            ->setSchema(
                                new Schema([
                                    'type' => 'string',
                                ])
                            )
                    )
                    ->addResponse(DefinitionResponse::create(200))
                    ->addResponse(DefinitionResponse::create(400))
                    ->addExample(
                        OperationExample::create('200.default')
                            ->setQueryParameters([
                                'foo' => 'bar',
                            ])
                            ->setResponse(ResponseExample::create())
                    )
                    ->addExample(
                        OperationExample::create('400')
                            ->setQueryParameters([
                                'foo' => '1234',
                            ])
                            ->setResponse(
                                ResponseExample::create([
                                    'message' => 'Bad request',
                                ])
                                    ->setStatusCode(400)
                            )
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 200.default',
                    new Request(
                        'GET',
                        new Uri('/test?foo=bar'),
                    ),
                    new Response(
                        200
                    ),
                ),
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 400',
                    new Request(
                        'GET',
                        new Uri('/test?foo=1234'),
                    ),
                    new Response(
                        400,
                        [
                            'content-type' => 'application/json',
                        ],
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
                    ->addRequestBody(
                        Body::create(
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
                        )
                    )
                    ->addResponse(
                        DefinitionResponse::create(201)
                    )
                    ->addResponse(
                        DefinitionResponse::create(400)
                    )
                    ->addExample(
                        OperationExample::create('201')
                            ->setBody(
                                BodyExample::create([
                                    'name' => 'John Doe',
                                    'age' => 25,
                                ])
                            )
                            ->setResponse(ResponseExample::create()->setStatusCode(201))
                    )
                    ->addExample(
                        OperationExample::create('400.missingParameter')
                            ->setBody(
                                BodyExample::create([
                                    'name' => 'John Doe',
                                ])
                            )
                            ->setResponse(
                                ResponseExample::create([
                                    'message' => 'Missing parameter',
                                ])
                                    ->setStatusCode(400)
                            )
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 201',
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
                    ExamplesPreparator::getName() . ' - test - 400.missingParameter',
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
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'message' => 'Missing parameter',
                        ])
                    ),
                ),
            ],
        ];

        yield 'with path parameters' => [
            Api::create()->addOperation(
                Operation::create('test', '/user/{userId}/comment/{commentId}')
                    ->addPathParameter(
                        Parameter::create('userId')->setSchema(
                            new Schema([
                                'type' => 'integer',
                            ])
                        )
                    )
                    ->addPathParameter(
                        Parameter::create('commentId')->setSchema(
                            new Schema([
                                'type' => 'integer',
                            ])
                        )
                    )
                    ->addResponse(DefinitionResponse::create(200))
                    ->addResponse(DefinitionResponse::create(400))
                    ->addExample(
                        OperationExample::create('200')
                            ->setPathParameters([
                                'userId' => 123,
                                'commentId' => 456,
                            ])
                            ->setResponse(
                                ResponseExample::create([
                                    'title' => 'foo',
                                    'content' => 'bar',
                                ])
                            )
                    )
                    ->addExample(
                        OperationExample::create('400.default')
                            ->setPathParameters([
                                'userId' => 'foo',
                                'commentId' => 'bar',
                            ])
                            ->setResponse(
                                ResponseExample::create([
                                    'message' => 'Bad request',
                                ])
                                    ->setStatusCode(400)
                            )
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 200',
                    new Request(
                        'GET',
                        new Uri('/user/123/comment/456'),
                    ),
                    new Response(
                        200,
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'title' => 'foo',
                            'content' => 'bar',
                        ])
                    ),
                ),
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 400.default',
                    new Request(
                        'GET',
                        new Uri('/user/foo/comment/bar'),
                    ),
                    new Response(
                        400,
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
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
                )
                    ->addHeader(
                        Parameter::create('x-next')
                            ->setSchema(
                                new Schema([
                                    'type' => 'string',
                                ])
                            )
                    )
                    ->addResponse(
                        DefinitionResponse::create(200)
                            ->addHeader(Parameter::create('x-foo'))
                    )
                    ->addResponse(DefinitionResponse::create(400))
                    ->addExample(
                        OperationExample::create('200')
                            ->setHeaders([
                                'x-next' => '/test?offset=20&limit=20',
                            ])
                            ->setResponse(
                                ResponseExample::create([
                                    'title' => 'foo',
                                    'content' => 'bar',
                                ])->setHeaders([
                                    'x-foo' => 'bar',
                                ])
                            )
                    )
                    ->addExample(
                        OperationExample::create('400')
                            ->setHeaders([
                                'x-next' => '123',
                            ])
                            ->setResponse(
                                ResponseExample::create([
                                    'message' => 'Bad request',
                                ])
                                    ->setStatusCode(400)
                            )
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 200',
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
                            'content-type' => [
                                'application/json',
                            ],
                            'x-foo' => 'bar',
                        ],
                        Json::encode([
                            'title' => 'foo',
                            'content' => 'bar',
                        ])
                    ),
                ),
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 400',
                    new Request(
                        'GET',
                        new Uri('/test'),
                        [
                            'x-next' => '123',
                        ]
                    ),
                    new Response(
                        400,
                        [
                            'content-type' => [
                                'application/json',
                            ],
                        ],
                        Json::encode([
                            'message' => 'Bad request',
                        ])
                    ),
                ),
            ],
        ];
    }
}
