<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Preparator\Error405TestCasesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error405TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error405TestCasesPreparator();
        $preparator->configure([
            'methods' => ['PATCH', 'PUT', 'GET', 'DELETE'],
        ]);
        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api->getOperations()),
            ['body']
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
                    'test',
                    new Request('PUT', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'test',
                    new Request('GET', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'test',
                    new Request('DELETE', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'test2',
                    new Request('PATCH', '/test2'),
                    new Response(405)
                ),
                new TestCase(
                    'test2',
                    new Request('PUT', '/test2'),
                    new Response(405)
                ),
                new TestCase(
                    'test2',
                    new Request('DELETE', '/test2'),
                    new Response(405)
                ),
            ],
        ];
    }
}
