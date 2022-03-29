<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\ParameterExample;
use APITester\Definition\RequestExample;
use APITester\Preparator\Error400RequiredFieldsTestCasesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use APITester\Util\Json;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error400RequiredFieldsTestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error400RequiredFieldsTestCasesPreparator();
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
        yield 'Required query param' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->setMethod('GET')
                        ->addQueryParameter(
                            (new Parameter('foo_query', true))->addExample(new ParameterExample('foo_query', 'foo1'))
                        )
                ),
            [
                new TestCase(
                    new Request('GET', '/test'),
                    new Response(400)
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
                        ->addQueryParameter(
                            (new Parameter('foo_query', true))->addExample(new ParameterExample('foo_query', 'foo1'))
                        )
                        ->addQueryParameter(
                            (new Parameter('bar_query', true))->addExample(new ParameterExample('bar_query', 'bar1'))
                        )
                ),
            [
                new TestCase(
                    new Request('GET', '/test?bar_query=bar1'),
                    new Response(400)
                ),
                new TestCase(
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
                        ->addQueryParameter(
                            (new Parameter('foo_query', true))->addExample(new ParameterExample('foo_query', 'foo1'))
                        )
                        ->addPathParameter(
                            (new Parameter('id', true))->addExample(new ParameterExample('id', '1234'))
                        )
                ),
            [
                new TestCase(
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
                        ->addQueryParameter(
                            (new Parameter('foo_query', true))->addExample(new ParameterExample('foo_query', 'foo1'))
                        )
                        ->addHeader(
                            (new Parameter('bar_header', true))->addExample(new ParameterExample('bar_header', 'bar1'))
                        )
                ),
            [
                new TestCase(
                    new Request('GET', '/test', [
                        'bar_header' => 'bar1',
                    ]),
                    new Response(400)
                ),
                new TestCase(
                    new Request('GET', '/test?foo_query=foo1'),
                    new Response(400)
                ),
            ],
        ];

        yield 'Multiple operations' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->setMethod('GET')
                        ->addQueryParameter(
                            (new Parameter('foo_query', true))->addExample(new ParameterExample('foo_query', 'foo1'))
                        )
                        ->addHeader(
                            (new Parameter('bar_header', true))->addExample(new ParameterExample('bar_header', 'bar1'))
                        )
                )
                ->addOperation(
                    Operation::create(
                        'test2',
                        '/test2'
                    )
                        ->setMethod('GET')
                        ->addQueryParameter(
                            (new Parameter('foo_query2', true))->addExample(new ParameterExample('foo_query2', 'foo'))
                        )
                ),
            [
                new TestCase(
                    new Request('GET', '/test', [
                        'bar_header' => 'bar1',
                    ]),
                    new Response(400)
                ),
                new TestCase(
                    new Request('GET', '/test?foo_query=foo1'),
                    new Response(400)
                ),
                new TestCase(
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
                        ->addRequest(
                            (new \APITester\Definition\Request(
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
                            ))->addExample(new RequestExample('foo', 'foo1'))
                        )
                ),
            [
                new TestCase(
                    new Request('POST', '/test', [], Json::encode([])),
                    new Response(400)
                ),
                new TestCase(
                    new Request('POST', '/test'),
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
                        ->addQueryParameter(
                            (new Parameter('foo_query', true))->addExample(new ParameterExample('foo_query', 'foo1'))
                        )
                        ->addRequest(
                            (new \APITester\Definition\Request(
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
                            ))->addExample(new RequestExample('foo', 'foo_body1'))
                        )
                ),
            [
                new TestCase(
                    new Request(
                        'POST',
                        '/test',
                        [],
                        Json::encode([
                            'foo' => 'foo_body1',
                        ])
                    ),
                    new Response(400)
                ),
                new TestCase(
                    new Request('POST', '/test?foo_query=foo1', [], Json::encode([])),
                    new Response(400)
                ),
                new TestCase(
                    new Request('POST', '/test?foo_query=foo1'),
                    new Response(400)
                ),
            ],
        ];
    }
}
