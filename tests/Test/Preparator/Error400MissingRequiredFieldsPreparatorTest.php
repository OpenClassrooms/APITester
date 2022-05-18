<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Body;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Error400MissingRequiredFieldsPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use APITester\Util\Json;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error400MissingRequiredFieldsPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error400MissingRequiredFieldsPreparator();
        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations())
        );
    }

    /**
     * @return iterable<string, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
        yield 'Required query param' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->setMethod('GET')
                        ->addQueryParameter(Parameter::create('foo_query'))
                        ->addExample(OperationExample::create('foo')->setQueryParameter('foo_query', 'foo1'))
                ),
            [
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName(
                    ) . ' - test - required_foo_query_param_missing_test',
                    new Request('GET', '/test'),
                    new Response(400),
                ),
            ],
        ];

        yield 'Required query params' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->setMethod('GET')
                        ->addQueryParameter(new Parameter('foo_query', true))
                        ->addQueryParameter(new Parameter('bar_query', true))
                        ->addExample(
                            OperationExample::create('foo')
                                ->setQueryParameter('foo_query', 'foo1')
                                ->setQueryParameter('bar_query', 'bar1')
                        )
                ),
            [
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName(
                    ) . ' - test - required_foo_query_param_missing_test',
                    new Request('GET', '/test?bar_query=bar1'),
                    new Response(400)
                ),
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName(
                    ) . ' - test - required_bar_query_param_missing_test',
                    new Request('GET', '/test?foo_query=foo1'),
                    new Response(400)
                ),
            ],
        ];

        yield 'Required query param and path param' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test/{id}'
                    )
                        ->setMethod('GET')
                        ->addQueryParameter(new Parameter('foo_query', true))
                        ->addPathParameter(new Parameter('id', true))
                        ->addExample(
                            OperationExample::create('foo')
                                ->setQueryParameter('foo_query', 'foo1')
                                ->setPathParameter('id', '1234')
                        )
                ),
            [
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName(
                    ) . ' - test - required_foo_query_param_missing_test',
                    new Request('GET', '/test/1234'),
                    new Response(400)
                ),
            ],
        ];

        yield 'Required header and query param' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->setMethod('GET')
                        ->addQueryParameter(new Parameter('foo_query', true))
                        ->addHeader(new Parameter('bar_header', true))
                        ->addExample(
                            OperationExample::create('foo')
                                ->setQueryParameter('foo_query', 'foo1')
                                ->setHeader('bar_header', 'bar1')
                        )
                ),
            [
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName(
                    ) . ' - test - required_foo_query_param_missing_test',
                    new Request('GET', '/test', [
                        'bar_header' => 'bar1',
                    ]),
                    new Response(400)
                ),
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName(
                    ) . ' - test - required_bar_header_param_missing_test',
                    new Request('GET', '/test?foo_query=foo1'),
                    new Response(400)
                ),
            ],
        ];

        yield 'Multiple operations' => [
            Api::create()
                ->addOperation(
                    Operation::create('test', '/test')
                        ->setMethod('GET')
                        ->addQueryParameter(new Parameter('foo_query', true))
                        ->addHeader(new Parameter('bar_header', true))
                        ->addExample(
                            OperationExample::create('foo')
                                ->setQueryParameter('foo_query', 'foo1')
                                ->setHeader('bar_header', 'bar1')
                        )
                )
                ->addOperation(
                    Operation::create(
                        'test2',
                        '/test2'
                    )
                        ->setMethod('GET')
                        ->addQueryParameter(new Parameter('foo_query2', true))
                        ->addExample(
                            OperationExample::create('foo2')
                                ->setQueryParameter('foo_query2', 'foo')
                        )
                ),
            [
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName(
                    ) . ' - test - required_foo_query_param_missing_test',
                    new Request('GET', '/test', [
                        'bar_header' => 'bar1',
                    ]),
                    new Response(400)
                ),
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName()
                    . ' - test - required_bar_header_param_missing_test',
                    new Request('GET', '/test?foo_query=foo1'),
                    new Response(400)
                ),
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName()
                    . ' - test2 - required_foo_query2_param_missing_test2',
                    new Request('GET', '/test2'),
                    new Response(400)
                ),
            ],
        ];

        yield 'Required body param' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test',
                        'POST'
                    )
                        ->addRequestBody(
                            new Body(
                                'application/json',
                                new Schema([
                                    'type' => 'object',
                                    'properties' => [
                                        'foo' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => ['foo'],
                                ])
                            )
                        )
                        ->addExample(
                            OperationExample::create('foo')
                                ->setBodyContent([
                                    'foo' => 'foo1',
                                ])
                        )
                ),
            [
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName() . ' - test - required_foo_body_field_missing',
                    new Request('POST', '/test', [
                        'content-type' => 'application/json',
                    ], Json::encode([])),
                    new Response(400)
                ),
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName() . ' - test - required_body_missing_test',
                    new Request('POST', '/test', [
                        'content-type' => 'application/json',
                    ], Json::encode([])),
                    new Response(400)
                ),
            ],
        ];

        yield 'Required body param and query param' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test',
                        'POST'
                    )
                        ->addQueryParameter(new Parameter('foo_query', true))
                        ->addRequestBody(
                            (new Body(
                                'application/json',
                                new Schema([
                                    'type' => 'object',
                                    'properties' => [
                                        'foo' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => ['foo'],
                                ])
                            ))
                        )
                        ->addExample(
                            OperationExample::create('foo')
                                ->setBodyContent([
                                    'foo' => 'foo_body1',
                                ])
                                ->setQueryParameter('foo_query', 'foo1')
                        )
                ),
            [
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName(
                    ) . ' - test - required_foo_query_param_missing_test',
                    new Request(
                        'POST',
                        '/test',
                        [
                            'content-type' => 'application/json',
                        ],
                        Json::encode([
                            'foo' => 'foo_body1',
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName() . ' - test - required_foo_body_field_missing',
                    new Request(
                        'POST',
                        '/test?foo_query=foo1',
                        [
                            'content-type' => 'application/json',
                        ],
                        Json::encode([])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName() . ' - test - required_body_missing_test',
                    new Request(
                        'POST',
                        '/test?foo_query=foo1',
                        [
                            'content-type' => 'application/json',
                        ],
                        Json::encode([])
                    ),
                    new Response(400)
                ),
            ],
        ];
    }
}
