<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Error413TestCasesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error413TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param array<string, array<mixed>> $config
     * @param TestCase[]                  $expected
     */
    public function test(array $config, Api $api, array $expected): void
    {
        $preparator = new Error413TestCasesPreparator();
        $preparator->configure($config);

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api->getOperations()),
            ['size', 'id', 'headerNames', 'groups']
        );
    }

    /**
     * @return iterable<string, array{array<string, mixed>,Api, array<TestCase>}>
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
                    new Request('GET', '/test?offset=0&limit=1000000000'),
                    new Response(413)
                ),
            ],
        ];

        yield 'Header range in Api && header defined in config' => [
            [
                'range' => [
                    [
                        'in' => 'header',
                        'names' => ['Range'],
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
                        ->addHeader(new Parameter('Range'))
                ),
            [
                new TestCase(
                    new Request('GET', '/test', [
                        'Range' => 'items=0-1000000000',
                    ]),
                    new Response(413)
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
                        'names' => ['Range'],
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
                        ->addHeader(new Parameter('Range'))
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
                    new Request('GET', '/test1', [
                        'Range' => 'items=0-1000000000',
                    ]),
                    new Response(413)
                ),
                new TestCase(
                    new Request('GET', '/test2?offset=0&limit=1000000000'),
                    new Response(413)
                ),
            ],
        ];

        yield 'Nothing in Api && header in config' => [
            [
                'range' => [
                    [
                        'in' => 'header',
                        'names' => ['Range'],
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
