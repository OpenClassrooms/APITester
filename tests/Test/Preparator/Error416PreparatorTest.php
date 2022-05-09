<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Error416Preparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error416PreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param array<string, array<mixed>> $config
     * @param TestCase[]                  $expected
     */
    public function test(array $config, Api $api, array $expected): void
    {
        $preparator = new Error416Preparator();
        $preparator->configure($config);

        Assert::objectsEqual(
            $expected,
            $preparator->getTestCases($api->getOperations())
        );
    }

    /**
     * @return iterable<string, array{array<string, mixed>, Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
        yield 'Query param range in Api && query param defined in config' => [
            [
                'range' => [
                    [
                        'in' => 'query',
                        'names' => ['offset', 'limit'],
                    ],
                ],
            ],
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->addQueryParameter(new Parameter('offset'))
                        ->addQueryParameter(new Parameter('limit'))
                ),
            [
                new TestCase(
                    'test/NonNumericRange',
                    new Request('GET', '/test?offset=foo&limit=bar'),
                    new Response(416)
                ),
                new TestCase(
                    'test/InversedRange',
                    new Request('GET', '/test?offset=20&limit=5'),
                    new Response(416)
                ),
                new TestCase(
                    'test/NegativeRange',
                    new Request('GET', '/test?offset=-5&limit=5'),
                    new Response(416)
                ),
            ],
        ];

        yield 'Header range in Api && header defined in config' => [
            [
                'range' => [
                    [
                        'in' => 'header',
                        'names' => ['RangeConfig'],
                        'unit' => 'items',
                    ],
                ],
            ],
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->addHeader(new Parameter('RangeConfig'))
                ),
            [
                new TestCase(
                    'test/NonNumericRange',
                    new Request('GET', '/test', [
                        'RangeConfig' => 'items=foo-bar',
                    ]),
                    new Response(416)
                ),
                new TestCase(
                    'test/InversedRange',
                    new Request('GET', '/test', [
                        'RangeConfig' => 'items=20-5',
                    ]),
                    new Response(416)
                ),
            ],
        ];

        yield 'Both header and query param in API && both in config' => [
            [
                'range' => [
                    [
                        'in' => 'query',
                        'names' => ['offset', 'limit'],
                    ],
                    [
                        'in' => 'header',
                        'names' => ['RangeConfig'],
                        'unit' => 'items',
                    ],
                ],
            ],
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test1',
                        '/test1'
                    )
                        ->addHeader(new Parameter('RangeConfig'))
                )
                ->addOperation(
                    Operation::create(
                        'test2',
                        '/test2'
                    )
                        ->addQueryParameter(new Parameter('offset'))
                        ->addQueryParameter(new Parameter('limit'))
                ),
            [
                new TestCase(
                    'test1/NonNumericRange',
                    new Request('GET', '/test1', [
                        'RangeConfig' => 'items=foo-bar',
                    ]),
                    new Response(416)
                ),
                new TestCase(
                    'test1/InversedRange',
                    new Request('GET', '/test1', [
                        'RangeConfig' => 'items=20-5',
                    ]),
                    new Response(416)
                ),
                new TestCase(
                    'test2/NonNumericRange',
                    new Request('GET', '/test2?offset=foo&limit=bar'),
                    new Response(416)
                ),
                new TestCase(
                    'test2/InversedRange',
                    new Request('GET', '/test2?offset=20&limit=5'),
                    new Response(416)
                ),
                new TestCase(
                    'test2/NegativeRange',
                    new Request('GET', '/test2?offset=-5&limit=5'),
                    new Response(416)
                ),
            ],
        ];

        yield 'Nothing in Api && header in config' => [
            [
                'range' => [
                    [
                        'in' => 'header',
                        'names' => ['RangeConfig'],
                        'unit' => 'items',
                    ],
                ],
            ],
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                ),
            [],
        ];
    }
}
