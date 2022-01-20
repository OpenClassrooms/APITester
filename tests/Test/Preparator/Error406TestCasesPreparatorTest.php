<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\OpenApi;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Preparator\Error406TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

final class Error406TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param \OpenAPITesting\Test\TestCase[] $expected
     */
    public function test(OpenApi $openApi, array $expected): void
    {
        $preparator = new Error406TestCasesPreparator();
        $preparator->configure([]);

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($openApi),
            ['size', 'id', 'headerNames', 'groups', 'headers']
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
                                '200' => [
                                    'content' => [
                                        'application/json' => [
                                            'schema' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'name' => [
                                                        'type' => 'string',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            [
                new TestCase(
                    'get_/test_200',
                    new Request('POST', '/test'),
                    new Response(406)
                ),
            ],
        ];
    }
}
