<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Error413Preparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;

final class Error413PreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param array<string, array<mixed>> $config
     * @param TestCase[]                  $expected
     */
    public function test(array $config, Api $api, array $expected): void
    {
        $preparator = new Error413Preparator();
        $preparator->configure($config);

        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['parent']
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
                    Error413Preparator::getName() . ' - test - TooLargeRange',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('offset', '0')
                        ->setQueryParameter('limit', '1000000000')
                        ->setResponse(ResponseExample::create('413')),
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
                    Error413Preparator::getName() . ' - test - TooLargeRange',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setHeader('RangeConfig', 'items=0-1000000000')
                        ->setResponse(ResponseExample::create('413')),
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
                    Error413Preparator::getName() . ' - test1 - TooLargeRange',
                    OperationExample::create('test1')
                        ->setPath('/test1')
                        ->setHeader('RangeConfig', 'items=0-1000000000')
                        ->setResponse(ResponseExample::create('413')),
                ),
                new TestCase(
                    Error413Preparator::getName() . ' - test2 - TooLargeRange',
                    OperationExample::create('test2')
                        ->setPath('/test2')
                        ->setQueryParameter('offset', '0')
                        ->setQueryParameter('limit', '1000000000')
                        ->setResponse(ResponseExample::create('413')),
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
