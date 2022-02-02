<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Preparator\Error400TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

final class Error400TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param array<array-key, mixed> $config
     * @param TestCase[] $expected
     */
    public function test(array $config, Api $api, array $expected): void
    {
        $preparator = new Error400TestCasesPreparator();
        $preparator->configure($config);
        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api),
            ['size', 'id', 'groups']
        );
    }

    /**
     * @return iterable<int, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
        yield 'Required query param' => [
            [],
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
                )
            ,
            [
                new TestCase(
                    'required_foo_query_param_missing_test',
                    new Request('GET', '/test'),
                    new Response(400)
                ),
            ],
        ];

        yield 'Required query params' => [
            [],
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
                    'required_foo_query_param_missing_test',
                    new Request('GET', '/test?bar_query=bar1'),
                    new Response(400)
                ),
                new TestCase(
                    'required_bar_query_param_missing_test',
                    new Request('GET', '/test?foo_query=foo1'),
                    new Response(400)
                ),
            ],
        ];

        yield 'Required query param and path param' => [
            [],
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
                    'required_foo_query_param_missing_test',
                    new Request('GET', '/test/1234'),
                    new Response(400)
                ),
            ],
        ];

        yield 'Required header and query param' => [
            [],
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
                    'required_foo_query_param_missing_test',
                    new Request('GET', '/test', ['bar_header' => 'bar1']),
                    new Response(400)
                ),
                new TestCase(
                    'required_bar_header_param_missing_test',
                    new Request('GET', '/test?foo_query=foo1'),
                    new Response(400)
                ),
            ],
        ];

//        yield 'Required cookie param' => [
//            [],
//            Api::create()
//                ->addOperation(
//                    Operation::create(
//                        'test',
//                        '/test'
//                    )
//                ),
//            [
//                new TestCase(
//                    'required_header_param_test',
//                    new Request('GET', '/test'),
//                    new Response(400)
//                ),
//            ],
//        ];

        yield 'Multiple operations' => [
            [],
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
                )
            ,
            [
                new TestCase(
                    'required_foo_query_param_missing_test',
                    new Request('GET', '/test', ['bar_header' => 'bar1']),
                    new Response(400)
                ),
                new TestCase(
                    'required_bar_header_param_missing_test',
                    new Request('GET', '/test?foo_query=foo1'),
                    new Response(400)
                ),
                new TestCase(
                    'required_foo_query2_param_missing_test2',
                    new Request('GET', '/test2'),
                    new Response(400)
                ),
            ],
        ];

//        yield 'Required body param' => [
//            [],
//            Api::create()
//                ->addOperation(
//                    Operation::create(
//                        'test',
//                        '/test'
//                    )
//                        ->addRequest(
//                            new \OpenAPITesting\Definition\Request(
//                                'application/json',
//                                new Schema([
//                                    'type' => 'object',
//                                    'properties' => [
//                                        'foo' => [
//                                            'type' => 'string',
//                                            'example' => 'foo1',
//                                        ],
//                                    ],
//                                    'required' => ['foo'],
//                                ])
//                            )
//                        )
//                ),
//            [
//                new TestCase(
//                    'required_foo_missing_test',
//                    new Request('GET', '/test', []),
//                    new Response(400)
//                ),
//            ],
//        ];
    }
}
