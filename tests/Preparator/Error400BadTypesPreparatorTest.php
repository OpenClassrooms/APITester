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
use APITester\Preparator\Error400BadTypesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use cebe\openapi\spec\Schema;

final class Error400BadTypesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error400BadTypesPreparator();
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
        yield 'For string query param' => [
            Api::create()
                ->addOperation(
                    Operation::create('test', '/test')
                        ->setMethod('GET')
                        ->addQueryParameter(
                            (new Parameter(
                                'foo_query',
                                true,
                                new Schema([
                                    'type' => 'string',
                                ])
                            ))
                        )
                ),
            [
            ],
        ];
        yield 'For int query param' => [
            Api::create()
                ->addOperation(
                    Operation::create('test', '/test')
                        ->setMethod('GET')
                        ->addQueryParameter(
                            (new Parameter(
                                'foo_query',
                                true,
                                new Schema([
                                    'type' => 'integer',
                                ])
                            ))
                        )
                ),
            [
                new TestCase(
                    Error400BadTypesPreparator::getName() . ' - test - foo_query_param_bad_string_type',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('foo_query', 'foo')
                        ->setResponse(ResponseExample::create('400'))
                ),
                new TestCase(
                    Error400BadTypesPreparator::getName() . ' - test - foo_query_param_bad_number_type',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('foo_query', '1.234')
                        ->setResponse(ResponseExample::create('400'))
                ),
                new TestCase(
                    Error400BadTypesPreparator::getName() . ' - test - foo_query_param_bad_boolean_type',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('foo_query', 'true')
                        ->setResponse(ResponseExample::create('400'))
                ),
                new TestCase(
                    Error400BadTypesPreparator::getName() . ' - test - foo_query_param_bad_array_type',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('foo_query', 'foo,bar')
                        ->setResponse(ResponseExample::create('400'))
                ),
            ],
        ];

        yield 'For int body field' => [
            Api::create()
                ->addOperation(
                    Operation::create('test', '/test')
                        ->setMethod('GET')
                        ->addRequestBody(
                            new Body(
                                new Schema([
                                    'type' => 'object',
                                    'properties' => [
                                        'foo' => [
                                            'type' => 'integer',
                                        ],
                                    ],
                                    'required' => ['foo'],
                                ]),
                                'application/json'
                            )
                        )->addExample(
                            OperationExample::create('foo')
                                ->setBody(
                                    BodyExample::create([
                                        'foo' => '123',
                                    ])
                                )
                        )
                ),
            [
                new TestCase(
                    Error400BadTypesPreparator::getName() . ' - test - foo_body_field_type_string',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setBodyContent([
                            'foo' => 'foo',
                        ])
                        ->setResponse(ResponseExample::create('400'))
                ),
                new TestCase(
                    Error400BadTypesPreparator::getName() . ' - test - foo_body_field_type_number',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setBodyContent([
                            'foo' => 1.234,
                        ])
                        ->setResponse(ResponseExample::create('400'))
                ),
                new TestCase(
                    Error400BadTypesPreparator::getName() . ' - test - foo_body_field_type_boolean',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setBodyContent([
                            'foo' => true,
                        ])
                        ->setResponse(ResponseExample::create('400')),
                ),
                new TestCase(
                    Error400BadTypesPreparator::getName() . ' - test - foo_body_field_type_array',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setBodyContent([
                            'foo' => ['foo', 'bar'],
                        ])
                        ->setResponse(ResponseExample::create('400')),
                ),
                new TestCase(
                    Error400BadTypesPreparator::getName() . ' - test - foo_body_field_type_object',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setBodyContent([
                            'foo' => [
                                'foo' => 'bar',
                            ],
                        ])
                        ->setResponse(ResponseExample::create('400')),
                ),
            ],
        ];
    }
}
