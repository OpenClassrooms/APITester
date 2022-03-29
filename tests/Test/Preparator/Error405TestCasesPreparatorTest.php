<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Definition\Response as DefinitionResponse;
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

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api->getOperations()),
            ['size', 'id', 'headerNames', 'groups']
        );
    }

    /**
     * @return iterable<int, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
        yield [
            Api::create()->addOperation(
                Operation::create('test', '/test')
                    ->addResponse(
                        DefinitionResponse::create(405)
                    )
            ),
            [
                new TestCase(
                    new Request('POST', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    new Request('PUT', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    new Request('PATCH', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    new Request('DELETE', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    new Request('HEAD', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    new Request('OPTIONS', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    new Request('TRACE', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    new Request('CONNECT', '/test'),
                    new Response(405)
                ),
            ],
        ];
    }
}
