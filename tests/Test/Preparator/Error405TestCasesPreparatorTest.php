<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Preparator\Error405TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

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
        $preparator->configure([]);

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api),
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
                    ->addResponse(DefinitionResponse::create())
            ),
            [
                new TestCase(
                    'POST_/test',
                    new Request('POST', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'PUT_/test',
                    new Request('PUT', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'PATCH_/test',
                    new Request('PATCH', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'DELETE_/test',
                    new Request('DELETE', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'HEAD_/test',
                    new Request('HEAD', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'OPTIONS_/test',
                    new Request('OPTIONS', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'TRACE_/test',
                    new Request('TRACE', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'CONNECT_/test',
                    new Request('CONNECT', '/test'),
                    new Response(405)
                ),
            ],
        ];
    }
}
