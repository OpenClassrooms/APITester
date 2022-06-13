<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Preparator\Error405Preparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;

final class Error405PreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error405Preparator();
        $preparator->configure([
            'methods' => ['PATCH', 'PUT', 'GET', 'DELETE'],
        ]);
        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['body', 'parent']
        );
    }

    /**
     * @return iterable<int, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
        yield [
            Api::create()
                ->addOperation(
                    Operation::create('test', '/test', 'PATCH')
                )->addOperation(
                    Operation::create('test', '/test', 'POST')
                )
                ->addOperation(
                    Operation::create('test2', '/test2')
                ),
            [
                new TestCase(
                    Error405Preparator::getName() . ' - test - UnsupportedMethod/PUT',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setMethod('PUT')
                        ->setResponse(ResponseExample::create('405')),
                ),
                new TestCase(
                    Error405Preparator::getName() . ' - test - UnsupportedMethod/GET',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setMethod('GET')
                        ->setResponse(ResponseExample::create('405')),
                ),
                new TestCase(
                    Error405Preparator::getName() . ' - test - UnsupportedMethod/DELETE',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setMethod('DELETE')
                        ->setResponse(ResponseExample::create('405')),
                ),
                new TestCase(
                    Error405Preparator::getName() . ' - test2 - UnsupportedMethod/PATCH',
                    OperationExample::create('test2')
                        ->setPath('/test2')
                        ->setMethod('PATCH')
                        ->setResponse(ResponseExample::create('405')),
                ),
                new TestCase(
                    Error405Preparator::getName() . ' - test2 - UnsupportedMethod/PUT',
                    OperationExample::create('test2')
                        ->setPath('/test2')
                        ->setMethod('PUT')
                        ->setResponse(ResponseExample::create('405')),
                ),
                new TestCase(
                    Error405Preparator::getName() . ' - test2 - UnsupportedMethod/DELETE',
                    OperationExample::create('test2')
                        ->setPath('/test2')
                        ->setMethod('DELETE')
                        ->setResponse(ResponseExample::create('405')),
                ),
            ],
        ];
    }
}
