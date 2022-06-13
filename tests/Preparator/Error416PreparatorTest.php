<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Error416Preparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;

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
            $preparator->doPrepare($api->getOperations()),
            ['parent']
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
                        'names' => ['from', 'to'],
                    ],
                ],
            ],
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->addQueryParameter(new Parameter('from'))
                        ->addQueryParameter(new Parameter('to'))
                ),
            [
                new TestCase(
                    Error416Preparator::getName() . ' - test - NonNumericRange',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('from', 'foo')
                        ->setQueryParameter('to', 'bar')
                        ->setResponse(ResponseExample::create('416')),
                ),
                new TestCase(
                    Error416Preparator::getName() . ' - test - InversedRange',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('from', '20')
                        ->setQueryParameter('to', '5')
                        ->setResponse(ResponseExample::create('416')),
                ),
                new TestCase(
                    Error416Preparator::getName() . ' - test - NegativeRange',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('from', '-5')
                        ->setQueryParameter('to', '5')
                        ->setResponse(ResponseExample::create('416')),
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
                    Error416Preparator::getName() . ' - test - NonNumericRange',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setHeader('RangeConfig', 'items=foo-bar')
                        ->setResponse(ResponseExample::create('416')),
                ),
                new TestCase(
                    Error416Preparator::getName() . ' - test - InversedRange',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setHeader('RangeConfig', 'items=20-5')
                        ->setResponse(ResponseExample::create('416')),
                ),
            ],
        ];

        yield 'Both header and query param in API && both in config' => [
            [
                'range' => [
                    [
                        'in' => 'query',
                        'names' => ['from', 'to'],
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
                        ->addQueryParameter(new Parameter('from'))
                        ->addQueryParameter(new Parameter('to'))
                ),
            [
                new TestCase(
                    Error416Preparator::getName() . ' - test1 - NonNumericRange',
                    OperationExample::create('test1')
                        ->setPath('/test1')
                        ->setHeader('RangeConfig', 'items=foo-bar')
                        ->setResponse(ResponseExample::create('416')),
                ),
                new TestCase(
                    Error416Preparator::getName() . ' - test1 - InversedRange',
                    OperationExample::create('test1')
                        ->setPath('/test1')
                        ->setHeader('RangeConfig', 'items=20-5')
                        ->setResponse(ResponseExample::create('416')),
                ),
                new TestCase(
                    Error416Preparator::getName() . ' - test2 - NonNumericRange',
                    OperationExample::create('test2')
                        ->setPath('/test2')
                        ->setQueryParameter('from', 'foo')
                        ->setQueryParameter('to', 'bar')
                        ->setResponse(ResponseExample::create('416')),
                ),
                new TestCase(
                    Error416Preparator::getName() . ' - test2 - InversedRange',
                    OperationExample::create('test2')
                        ->setPath('/test2')
                        ->setQueryParameter('from', '20')
                        ->setQueryParameter('to', '5')
                        ->setResponse(ResponseExample::create('416')),
                ),
                new TestCase(
                    Error416Preparator::getName() . ' - test2 - NegativeRange',
                    OperationExample::create('test2')
                        ->setPath('/test2')
                        ->setQueryParameter('from', '-5')
                        ->setQueryParameter('to', '5')
                        ->setResponse(ResponseExample::create('416')),
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
