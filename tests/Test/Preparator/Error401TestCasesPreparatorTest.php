<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\OpenApi;
use Firebase\JWT\JWT;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Preparator\Error401TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

final class Error401TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param \OpenAPITesting\Test\TestCase[] $expected
     */
    public function test(OpenApi $openApi, array $expected): void
    {
        $preparator = new Error401TestCasesPreparator();
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
                    '/test/oauth2' => [
                        'get' => [
                            'operationId' => 'test',
                            'responses' => [
                                '401' => [],
                            ],
                            'security' => [
                                [
                                    'oauth2_test' => ['write:pets'],
                                ],
                            ],
                        ],
                    ],
                    '/test/api/key/header' => [
                        'get' => [
                            'operationId' => 'test',
                            'responses' => [
                                '401' => [],
                            ],
                            'security' => [
                                [
                                    'api_key_header' => [],
                                ],
                            ],
                        ],
                    ],
                    '/test/api/key/cookie' => [
                        'get' => [
                            'operationId' => 'test',
                            'responses' => [
                                '401' => [],
                            ],
                            'security' => [
                                [
                                    'api_key_cookie' => [],
                                ],
                            ],
                        ],
                    ],
                    '/test/api/key/query' => [
                        'get' => [
                            'operationId' => 'test',
                            'responses' => [
                                '401' => [],
                            ],
                            'security' => [
                                [
                                    'api_key_query' => [],
                                ],
                            ],
                        ],
                    ],
                    '/test/basic' => [
                        'get' => [
                            'operationId' => 'test',
                            'responses' => [
                                '401' => [],
                            ],
                            'security' => [
                                [
                                    'basic_test' => [],
                                ],
                            ],
                        ],
                    ],
                    '/test/bearer' => [
                        'get' => [
                            'operationId' => 'test',
                            'responses' => [
                                '401' => [],
                            ],
                            'security' => [
                                [
                                    'bearer_test' => [
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'components' => [
                    'securitySchemes' => [
                        'oauth2_test' => [
                            'type' => 'oauth2',
                            'flows' => [
                                'implicit' => [
                                    'authorizationUrl' => 'https://petstore3.swagger.io/oauth/authorize',
                                    'scopes' => [
                                        'write:pets' => 'modify pets in your account',
                                        'read:pets' => 'read your pets',
                                    ],
                                ],
                            ],
                        ],
                        'api_key_header' => [
                            'type' => 'apiKey',
                            'name' => 'api_key',
                            'in' => 'header',
                        ],
                        'api_key_cookie' => [
                            'type' => 'apiKey',
                            'name' => 'api_key',
                            'in' => 'cookie',
                        ],
                        'api_key_query' => [
                            'type' => 'apiKey',
                            'name' => 'api_key',
                            'in' => 'query',
                        ],
                        'basic_test' => [
                            'type' => 'http',
                            'scheme' => 'basic',
                        ],
                        'bearer_test' => [
                            'type' => 'http',
                            'scheme' => 'bearer',
                        ],
                    ],
                ],
            ]),
            [
                new TestCase(
                    'test',
                    new Request(
                        'GET',
                        '/test/oauth2?1=1',
                        [
                            'Authorization' => 'Bearer ' . JWT::encode([
                                    'test' => 1234,
                                ], 'abcd'),
                        ]
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test',
                    new Request(
                        'GET',
                        '/test/api/key/header?1=1',
                        [
                            'api_key' => Error401TestCasesPreparator::FAKE_API_KEY,
                        ]
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test',
                    new Request(
                        'GET',
                        '/test/api/key/cookie?1=1',
                        [
                            'Cookie' => 'api_key=' . Error401TestCasesPreparator::FAKE_API_KEY,
                        ]
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test',
                    new Request(
                        'GET',
                        '/test/api/key/query?1=1&api_key=' . Error401TestCasesPreparator::FAKE_API_KEY
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test',
                    new Request(
                        'GET',
                        '/test/basic?1=1',
                        [
                            'Authorization' => 'Basic ' . base64_encode('aaaa:bbbbb'),
                        ]
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test',
                    new Request(
                        'GET',
                        '/test/bearer?1=1',
                        [
                            'Authorization' => 'Bearer ' . JWT::encode([
                                    'test' => 1234,
                                ], 'abcd'),
                        ]
                    ),
                    new Response(401)
                ),
            ],
        ];
    }
}
