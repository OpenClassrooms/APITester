<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\OpenApi;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Preparator\Error405TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

final class Error405TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
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
     * @return iterable<int, array{OpenApi, array<TestCase>}>
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
                    new Request('POST', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'put_/test',
                    new Request('PUT', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'patch_/test',
                    new Request('PATCH', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'delete_/test',
                    new Request('DELETE', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'head_/test',
                    new Request('HEAD', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'options_/test',
                    new Request('OPTIONS', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'trace_/test',
                    new Request('TRACE', '/test'),
                    new Response(405)
                ),
                new TestCase(
                    'connect_/test',
                    new Request('CONNECT', '/test'),
                    new Response(405)
                ),
            ],
        ];
    }
}
