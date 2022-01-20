<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\OpenApi;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Preparator\Error404TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;

final class Error404TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param \OpenAPITesting\Test\TestCase[] $expected
     */
    public function test(OpenApi $openApi, array $expected): void
    {
        $preparator = new Error404TestCasesPreparator();
        $preparator->configure([]);

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($openApi),
            ['size', 'id', 'headerNames', 'groups', 'stream']
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
                    '/test/{id}' => [
                        'get' => [
                            'operationId' => 'getTest',
                            'parameters' => [
                                [
                                    'name' => 'id',
                                    'in' => 'path',
                                ],
                            ],
                            'responses' => [
                                '200' => [],
                                '404' => [
                                    'description' => 'description test',
                                ],
                            ],
                        ],
                    ],
                    '/test' => [
                        'post' => [
                            'operationId' => 'postTest',
                            'requestBody' => [
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'required' => ['name'],
                                            'properties' => [
                                                'name' => [
                                                    'type' => 'string',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'responses' => [
                                '200' => [],
                                '404' => [
                                    'description' => 'description test',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            [
                new TestCase(
                    'getTest',
                    new Request('GET', '/test/-9999'),
                    new Response(404, [], 'description test')
                ),
                new TestCase(
                    'postTest',
                    new Request('POST', '/test', [], Json::encode([
                        'name' => 'aaa',
                    ])),
                    // Body not verified since it is random
                    new Response(404, [], 'description test')
                ),
            ],
        ];
    }
}
