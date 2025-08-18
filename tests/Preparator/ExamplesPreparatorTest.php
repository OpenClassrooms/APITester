<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Config\Filters;
use APITester\Definition\Api;
use APITester\Definition\Body;
use APITester\Definition\Collection\Scopes;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Definition\Security\HttpSecurity;
use APITester\Definition\Token;
use APITester\Preparator\Config\ExamplesPreparatorConfig;
use APITester\Preparator\ExamplesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use cebe\openapi\spec\Schema;

/**
 * @internal
 */
final class ExamplesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigureConfig(): void
    {
        $preparator = new ExamplesPreparator();
        $preparator->configure([
            'extensionPath' => '/foo/bar/',
        ]);
        /** @var ExamplesPreparatorConfig $config */
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

        $this->addTokens($preparator);
        $preparator->configure([]);
        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['parent']
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
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('foo', 'bar')
                        ->setResponse(ResponseExample::create('200')),
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
                                ResponseExample::create(null, [
                                    'message' => 'Bad request',
                                ])
                                    ->setStatusCode('400')
                            )
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 200.default',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('foo', 'bar')
                        ->setResponse(ResponseExample::create('200')),
                ),
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 400',
                    OperationExample::create('test1')
                        ->setPath('/test')
                        ->setQueryParameter('foo', '1234')
                        ->setResponse(
                            ResponseExample::create('400', [
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
                            ->setResponse(ResponseExample::create()->setStatusCode('201'))
                    )
                    ->addExample(
                        OperationExample::create('400.missingParameter')
                            ->setBody(
                                BodyExample::create([
                                    'name' => 'John Doe',
                                ])
                            )
                            ->setResponse(
                                ResponseExample::create(null, [
                                    'message' => 'Missing parameter',
                                ])
                                    ->setStatusCode('400')
                            )
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 201',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setMethod('POST')
                        ->setBodyContent([
                            'name' => 'John Doe',
                            'age' => 25,
                        ])
                        ->setResponse(
                            ResponseExample::create('201')
                        ),
                ),
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 400.missingParameter',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setMethod('POST')
                        ->setBodyContent([
                            'name' => 'John Doe',
                        ])
                        ->setResponse(
                            ResponseExample::create('400', [
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
                                ResponseExample::create(null, [
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
                                ResponseExample::create(null, [
                                    'message' => 'Bad request',
                                ])
                                    ->setStatusCode('400')
                            )
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 200',
                    OperationExample::create('test')
                        ->setPath('/user/123/comment/456')
                        ->setResponse(
                            ResponseExample::create('200', [
                                'title' => 'foo',
                                'content' => 'bar',
                            ])
                        ),
                ),
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 400.default',
                    OperationExample::create('test')
                        ->setPath('/user/foo/comment/bar')
                        ->setResponse(
                            ResponseExample::create('400', [
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
                                ResponseExample::create(null, [
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
                                ResponseExample::create(null, [
                                    'message' => 'Bad request',
                                ])
                                    ->setStatusCode('400')
                            )
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 200',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setHeaders([
                            'x-next' => '/test?offset=20&limit=20',
                        ])
                        ->setResponse(
                            ResponseExample::create('200', [
                                'title' => 'foo',
                                'content' => 'bar',
                            ])->setHeaders([
                                'x-foo' => 'bar',
                            ])
                        ),
                ),
                new TestCase(
                    ExamplesPreparator::getName() . ' - test - 400',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setHeaders([
                            'x-next' => '123',
                        ])
                        ->setResponse(
                            ResponseExample::create('400', [
                                'message' => 'Bad request',
                            ])
                                ->setStatusCode('400'),
                        ),
                ),
            ],
        ];

        yield 'with filtered tokens' => [
            Api::create()->addOperation(
                Operation::create('filtered_token_test', '/tokens')
                    ->addSecurity(
                        HttpSecurity::create(
                            'bearer_test',
                            'bearer',
                            scopes: Scopes::fromNames(['scope5'])
                        )
                    )
                    ->addResponse(DefinitionResponse::create(200))
                    ->addExample(
                        OperationExample::create('200.default')
                            ->setResponse(new ResponseExample())
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - filtered_token_test - 200.default',
                    OperationExample::create('filtered_token_test')
                        ->setPath('/tokens')
                        ->setHeaders([
                            'Authorization' => ['Bearer 3333'],
                        ])
                        ->setResponse(ResponseExample::create('200')),
                ),
            ],
        ];

        yield 'without filtered tokens but multiple options' => [
            Api::create()->addOperation(
                Operation::create('unfiltered_token_test', '/tokens')
                    ->addSecurity(
                        HttpSecurity::create(
                            'bearer_test',
                            'bearer',
                            scopes: Scopes::fromNames(['scope5'])
                        )
                    )
                    ->addResponse(DefinitionResponse::create(200))
                    ->addExample(
                        OperationExample::create('200.default')
                            ->setResponse(new ResponseExample())
                    )
            ),
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - unfiltered_token_test - 200.default',
                    OperationExample::create('unfiltered_token_test')
                        ->setPath('/tokens')
                        ->setHeaders([
                            'Authorization' => ['Bearer 1111'],
                        ])
                        ->setResponse(ResponseExample::create('200')),
                ),
            ],
        ];
    }

    public function testSchemaValidationDisabledForBaselineOperation(): void
    {
        $preparator = new ExamplesPreparator();

        $preparator->configure([
            'schemaValidation' => true,
        ]);

        $api = Api::create()->addOperation(
            Operation::create('operationIdInBaseline', '/test')
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
        );
        $filters = new Filters(
            schemaValidationBaseline: __DIR__ . '/../../tests/Fixtures/Config/schema-validation-baseline.yaml'
        );
        $preparator->setSchemaValidationBaseline($filters->getSchemaValidationBaseline());

        Assert::objectsEqual(
            [
                new TestCase(
                    ExamplesPreparator::getName() . ' - operationIdInBaseline - 200.default',
                    OperationExample::create('operationIdInBaseline')
                        ->setPath('/test')
                        ->setQueryParameter('foo', 'bar')
                        ->setResponse(ResponseExample::create('200')),
                    schemaValidation: false
                ),
            ],
            $preparator->doPrepare($api->getOperations()),
            ['parent']
        );
    }

    private function addTokens(ExamplesPreparator $preparator): void
    {
        $preparator->addToken(
            new Token(
                'token1',
                'oauth2_implicit',
                '1111',
                [
                    'scope1',
                    'scope2',
                    'scope5',
                ],
            )
        )
            ->addToken(
                new Token(
                    'token2',
                    'oauth2_implicit',
                    '2222',
                    [
                        'scope3',
                        'scope4',
                    ],
                )
            )
            ->addToken(
                new Token(
                    'token3',
                    'oauth2_implicit',
                    '3333',
                    [
                        'scope5',
                    ],
                    filters: new Filters(include: [[
                        'id' => 'filtered_token_test',
                    ]])
                )
            )
        ;
    }
}
