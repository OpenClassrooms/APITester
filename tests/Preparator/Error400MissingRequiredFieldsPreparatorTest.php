<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Body;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Error400MissingRequiredFieldsPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use cebe\openapi\spec\Schema;

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
            $preparator->doPrepare($api->getOperations()),
            ['parent']
        );
    }

    /**
     * @return iterable<string, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
//        yield 'Required query param' => [
//            Api::create()
//                ->addOperation(
//                    Operation::create('test', '/test')
//                        ->setMethod('GET')
//                        ->addQueryParameter(Parameter::create('foo_query'))
//                        ->addExample(OperationExample::create('foo')->setQueryParameter('foo_query', 'foo1'))
//                ),
//            [
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_foo_query_param_missing_test',
//                    OperationExample::create('test')
//                        ->setPath('/test')
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//            ],
//        ];
//
//        yield 'Required query params' => [
//            Api::create()
//                ->addOperation(
//                    Operation::create('test', '/test')
//                        ->setMethod('GET')
//                        ->addQueryParameter(new Parameter('foo_query', true))
//                        ->addQueryParameter(new Parameter('bar_query', true))
//                        ->addExample(
//                            OperationExample::create('foo')
//                                ->setQueryParameter('foo_query', 'foo1')
//                                ->setQueryParameter('bar_query', 'bar1')
//                        )
//                ),
//            [
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_foo_query_param_missing_test',
//                    OperationExample::create('test')
//                        ->setPath('/test')
//                        ->setQueryParameter('bar_query', 'bar1')
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_bar_query_param_missing_test',
//                    OperationExample::create('test')
//                        ->setPath('/test')
//                        ->setQueryParameter('foo_query', 'foo1')
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//            ],
//        ];
//
//        yield 'Required query param and path param' => [
//            Api::create()
//                ->addOperation(
//                    Operation::create('test', '/test/{id}')
//                        ->setMethod('GET')
//                        ->addQueryParameter(new Parameter('foo_query', true))
//                        ->addPathParameter(new Parameter('id', true))
//                        ->addExample(
//                            OperationExample::create('foo')
//                                ->setQueryParameter('foo_query', 'foo1')
//                                ->setPathParameter('id', '1234')
//                        )
//                ),
//            [
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_foo_query_param_missing_test',
//                    OperationExample::create('test')
//                        ->setPath('/test/{id}')
//                        ->setPathParameter('id', '1234')
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//            ],
//        ];
//
//        yield 'Required header and query param' => [
//            Api::create()
//                ->addOperation(
//                    Operation::create('test', '/test')
//                        ->setMethod('GET')
//                        ->addQueryParameter(new Parameter('foo_query', true))
//                        ->addHeader(new Parameter('bar_header', true))
//                        ->addExample(
//                            OperationExample::create('foo')
//                                ->setQueryParameter('foo_query', 'foo1')
//                                ->setHeader('bar_header', 'bar1')
//                        )
//                ),
//            [
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_foo_query_param_missing_test',
//                    OperationExample::create('test')
//                        ->setPath('/test')
//                        ->setHeader('bar_header', 'bar1')
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_bar_header_param_missing_test',
//                    OperationExample::create('test')
//                        ->setPath('/test')
//                        ->setQueryParameter('foo_query', 'foo1')
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//            ],
//        ];
//
//        yield 'Multiple operations' => [
//            Api::create()
//                ->addOperation(
//                    Operation::create('test', '/test')
//                        ->setMethod('GET')
//                        ->addQueryParameter(new Parameter('foo_query', true))
//                        ->addHeader(new Parameter('bar_header', true))
//                        ->addExample(
//                            OperationExample::create('foo')
//                                ->setQueryParameter('foo_query', 'foo1')
//                                ->setHeader('bar_header', 'bar1')
//                        )
//                )
//                ->addOperation(
//                    Operation::create(
//                        'test2',
//                        '/test2'
//                    )
//                        ->setMethod('GET')
//                        ->addQueryParameter(new Parameter('foo_query2', true))
//                        ->addExample(
//                            OperationExample::create('foo2')
//                                ->setQueryParameter('foo_query2', 'foo')
//                        )
//                ),
//            [
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_foo_query_param_missing_test',
//                    OperationExample::create('test')
//                        ->setPath('/test')
//                        ->setHeader('bar_header', 'bar1')
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_bar_header_param_missing_test',
//                    OperationExample::create('test')
//                        ->setPath('/test')
//                        ->setQueryParameter('foo_query', 'foo1')
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test2 - required_foo_query2_param_missing_test2',
//                    OperationExample::create('test')
//                        ->setPath('/test2')
//                        ->setResponse(ResponseExample::create('400'))
//
//                ),
//            ],
//        ];
//
//        yield 'Required body param' => [
//            Api::create()
//                ->addOperation(
//                    Operation::create(
//                        'test',
//                        '/test',
//                        'POST'
//                    )
//                        ->addRequestBody(
//                            new Body(
//                                new Schema([
//                                    'type' => 'object',
//                                    'properties' => [
//                                        'foo' => [
//                                            'type' => 'string',
//                                        ],
//                                    ],
//                                    'required' => ['foo'],
//                                ]),
//                                'application/json'
//                            )
//                        )
//                        ->addExample(
//                            OperationExample::create('foo')
//                                ->setBodyContent([
//                                    'foo' => 'foo1',
//                                ])
//                        )
//                ),
//            [
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_foo_body_field_missing',
//                    OperationExample::create('test')
//                        ->setPath('/test')
//                        ->setMethod('POST')
//                        ->setBodyContent([])
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//                new TestCase(
//                    Error400MissingRequiredFieldsPreparator::getName()
//                    . ' - test - required_body_missing_test',
//                    OperationExample::create('test')
//                        ->setPath('/test')
//                        ->setMethod('POST')
//                        ->setBodyContent([])
//                        ->setResponse(ResponseExample::create('400'))
//                ),
//            ],
//        ];

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
                                new Schema([
                                    'type' => 'object',
                                    'properties' => [
                                        'foo' => [
                                            'type' => 'string',
                                        ],
                                        'bar' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => ['foo'],
                                ]),
                                'application/json'
                            ))
                                ->setRequired()
                        )
                        ->addExample(
                            OperationExample::create('foo')
                                ->setBodyContent([
                                    'foo' => 'foo_body1',
                                    'bar' => 'bar_body1',
                                ])
                                ->setQueryParameter('foo_query', 'foo1')
                        )
                ),
            [
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName()
                    . ' - test - required_foo_query_param_missing_test',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setMethod('POST')
                        ->setBodyContent([
                            'foo' => 'foo_body1',
                            'bar' => 'bar_body1',
                        ])
                        ->setResponse(ResponseExample::create('400')),
                ),
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName()
                    . ' - test - required_foo_body_field_missing',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setMethod('POST')
                        ->setBodyContent([
                            'bar' => 'bar_body1',
                        ])
                        ->setHeader('content-type', 'application/json')
                        ->setQueryParameter('foo_query', 'foo1')
                        ->setResponse(ResponseExample::create('400'))
                ),
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName()
                    . ' - test - required_body_missing_test',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setMethod('POST')
                        ->setBody(BodyExample::create())
                        ->setHeader('content-type', 'application/json')
                        ->setQueryParameter('foo_query', 'foo1')
                        ->setResponse(ResponseExample::create('400'))
                ),
            ],
        ];

        yield 'Unrequired body' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test',
                        'POST'
                    )
                        ->addRequestBody(
                            (new Body(
                                new Schema([
                                    'type' => 'object',
                                    'properties' => [
                                        'foo' => [
                                            'type' => 'string',
                                        ],
                                        'bar' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => ['foo'],
                                ]),
                                'application/json'
                            ))
                        )
                        ->addExample(
                            OperationExample::create('foo')
                                ->setBodyContent([
                                    'foo' => 'foo_body1',
                                    'bar' => 'bar_body1',
                                ])
                                ->setQueryParameter('foo_query', 'foo1')
                        )
                ),
            [
                new TestCase(
                    Error400MissingRequiredFieldsPreparator::getName()
                    . ' - test - required_foo_body_field_missing',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setMethod('POST')
                        ->setBodyContent([
                            'bar' => 'bar_body1',
                        ])
                        ->setHeader('content-type', 'application/json')
                        ->setResponse(ResponseExample::create('400'))
                ),
            ],
        ];
    }
}
