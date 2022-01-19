<?php

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\OpenApi;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Preparator\Error405TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

class Error405TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param \OpenAPITesting\Test\TestCase[] $expected
     */
    public function test(OpenApi $openApi, array $expected): void
    {
        $preparator = new Error405TestCasesPreparator();
        $preparator->configure([]);

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($openApi),
            ['size', 'id', 'headerNames', 'groups']
        );
    }

    /**
     * @return iterable<array-key, \OpenAPITesting\Test\TestCase[][]>
     */
    public function getData(): iterable
    {
        yield [
            new OpenApi([
                'openapi' => '3.0.2',
                'info' => [
                    'title' => 'Test API',
                    'version' => '1.0.0',
                ],
                'paths' => [
                    '/test' => [
                        'get' => [
                            'operationId' => 'test',
                            'responses' => [
                                '200' => [],
                            ],
                        ],
                    ],
                ],
            ]),
            [
                new TestCase(
                    'post_/test',
                    new Request('post', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'put_/test',
                    new Request('put', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'patch_/test',
                    new Request('patch', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'delete_/test',
                    new Request('delete', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'head_/test',
                    new Request('head', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'options_/test',
                    new Request('options', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'trace_/test',
                    new Request('trace', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'connect_/test',
                    new Request('connect', '/test'),
                    new Response(405)
                ),
            ],
        ];
    }
}
