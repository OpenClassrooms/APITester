<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\RequestExample;
use OpenAPITesting\Preparator\Error400BadTypesTestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;

final class Error400BadTypesTestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error400BadTypesTestCasesPreparator();
        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api->getOperations()),
            ['size', 'id', 'groups']
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
                    'foo_query_param_type_string_test',
                    new Request('GET', '/test?foo_query=foo'),
                    new Response(400)
                ),
                new TestCase(
                    'foo_query_param_type_number_test',
                    new Request('GET', '/test?foo_query=1.234'),
                    new Response(400)
                ),
                new TestCase(
                    'foo_query_param_type_boolean_test',
                    new Request('GET', '/test?foo_query=true'),
                    new Response(400)
                ),
                new TestCase(
                    'foo_query_param_type_array_test',
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
                        ->addRequest(
                            (new \OpenAPITesting\Definition\Request(
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
                            ))->addExample(new RequestExample('foo', '123'))
                        )
                ),
            [
                new TestCase(
                    'foo_body_field_type_string_test',
                    new Request(
                        'GET',
                        '/test',
                        [],
                        Json::encode([
                            'foo' => 'foo',
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    'foo_body_field_type_number_test',
                    new Request(
                        'GET',
                        '/test',
                        [],
                        Json::encode([
                            'foo' => 1.234,
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    'foo_body_field_type_boolean_test',
                    new Request(
                        'GET',
                        '/test',
                        [],
                        Json::encode([
                            'foo' => true,
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    'foo_body_field_type_array_test',
                    new Request(
                        'GET',
                        '/test',
                        [],
                        Json::encode([
                            'foo' => ['foo', 'bar'],
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    'foo_body_field_type_object_test',
                    new Request(
                        'GET',
                        '/test',
                        [],
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
