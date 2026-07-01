<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Schema\Entity\Api;
use APITester\Schema\Entity\Body;
use APITester\Schema\Entity\Example\BodyExample;
use APITester\Schema\Entity\Example\OperationExample;
use APITester\Schema\Entity\Example\ResponseExample;
use APITester\Schema\Entity\Operation;
use APITester\Schema\Entity\Parameter;
use APITester\Test\Entity\TestCase;
use APITester\Test\Preparator\Error400BadTypesPreparator;
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
    public static function getData(): iterable
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

        yield 'For floating-point number body field' => [
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
                                            'type' => 'number',
                                            'format' => 'double',
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
                                        'foo' => 3.14,
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
