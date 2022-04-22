<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Body;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Error400BadTypesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use APITester\Util\Json;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

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
            $preparator->getTestCases($api->getOperations())
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
                    Operation::create(
                        'test',
                        '/test'
                    )
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
                    Operation::create(
                        'test',
                        '/test'
                    )
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
                    'test/foo_query_param_bad_string_type',
                    new Request('GET', '/test?foo_query=foo'),
                    new Response(400)
                ),
                new TestCase(
                    'test/foo_query_param_bad_number_type',
                    new Request('GET', '/test?foo_query=1.234'),
                    new Response(400)
                ),
                new TestCase(
                    'test/foo_query_param_bad_boolean_type',
                    new Request('GET', '/test?foo_query=true'),
                    new Response(400)
                ),
                new TestCase(
                    'test/foo_query_param_bad_array_type',
                    new Request('GET', '/test?foo_query=foo%2Cbar'),
                    new Response(400)
                ),
            ],
        ];

        yield 'For int body field' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->setMethod('GET')
                        ->addRequestBody(
                            new Body(
                                'application/json',
                                new Schema([
                                    'type' => 'object',
                                    'properties' => [
                                        'foo' => [
                                            'type' => 'integer',
                                        ],
                                    ],
                                    'required' => ['foo'],
                                ])
                            )
                        )->addExample(
                            OperationExample::create('foo')
                                ->setBody(BodyExample::create([
                                    'foo' => '123',
                                ]))
                        )
                ),
            [
                new TestCase(
                    'test/foo_body_field_type_string',
                    new Request(
                        'GET',
                        '/test',
                        [
                            'content-type' => ['application/json'],
                        ],
                        Json::encode([
                            'foo' => 'foo',
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    'test/foo_body_field_type_number',
                    new Request(
                        'GET',
                        '/test',
                        [
                            'content-type' => ['application/json'],
                        ],
                        Json::encode([
                            'foo' => 1.234,
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    'test/foo_body_field_type_boolean',
                    new Request(
                        'GET',
                        '/test',
                        [
                            'content-type' => ['application/json'],
                        ],
                        Json::encode([
                            'foo' => true,
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    'test/foo_body_field_type_array',
                    new Request(
                        'GET',
                        '/test',
                        [
                            'content-type' => ['application/json'],
                        ],
                        Json::encode([
                            'foo' => ['foo', 'bar'],
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    'test/foo_body_field_type_object',
                    new Request(
                        'GET',
                        '/test',
                        [
                            'content-type' => ['application/json'],
                        ],
                        Json::encode([
                            'foo' => [
                                'foo' => 'bar',
                            ],
                        ])
                    ),
                    new Response(400)
                ),
            ],
        ];
    }
}