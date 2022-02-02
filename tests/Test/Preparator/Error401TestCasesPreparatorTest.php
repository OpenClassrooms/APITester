<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use Firebase\JWT\JWT;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Definition\Security\ApiKeySecurity;
use OpenAPITesting\Definition\Security\HttpSecurity;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2ImplicitSecurity;
use OpenAPITesting\Preparator\Error401TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

final class Error401TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $openApi, array $expected): void
    {
        $preparator = new Error401TestCasesPreparator();
        $preparator->configure([]);

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($openApi),
            ['size', 'id', 'headerNames', 'groups', 'excludedFields']
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
                    Operation::create('test1', '/test/oauth2')
                        ->addResponse(DefinitionResponse::create()->setStatusCode(401))
                        ->addSecurity(
                            OAuth2ImplicitSecurity::create(
                                'oauth2_test',
                                'https://petstore3.swagger.io/oauth/authorize',
                            )->addScopeFromString('write:pets')
                        )
                )
                ->addOperation(
                    Operation::create('test2', '/test/api/key/header')
                        ->addResponse(DefinitionResponse::create()->setStatusCode(401))
                        ->addSecurity(
                            ApiKeySecurity::create(
                                'api_key_header',
                                'api_key',
                                'header',
                            )
                        )
                )
                ->addOperation(
                    Operation::create('test3', '/test/api/key/cookie')
                        ->addResponse(DefinitionResponse::create()->setStatusCode(401))
                        ->addSecurity(
                            ApiKeySecurity::create(
                                'api_key_cookie',
                                'api_key',
                                'cookie',
                            )
                        )
                )
                ->addOperation(
                    Operation::create('test4', '/test/api/key/query')
                        ->addResponse(DefinitionResponse::create()->setStatusCode(401))
                        ->addSecurity(
                            ApiKeySecurity::create(
                                'api_key_query',
                                'api_key',
                                'query',
                            )
                        )
                )
                ->addOperation(
                    Operation::create('test5', '/test/basic')
                        ->addResponse(DefinitionResponse::create()->setStatusCode(401))
                        ->addSecurity(
                            HttpSecurity::create(
                                'basic_test',
                                'basic',
                            )
                        )
                )
                ->addOperation(
                    Operation::create('test6', '/test/bearer')
                        ->addResponse(DefinitionResponse::create()->setStatusCode(401))
                        ->addSecurity(
                            HttpSecurity::create(
                                'bearer_test',
                                'bearer',
                            )
                        )
                ),
            [
                new TestCase(
                    'test1_401_oauth2_implicit',
                    new Request(
                        'GET',
                        '/test/oauth2',
                        [
                            'Authorization' => 'Bearer ' . JWT::encode([
                                'test' => 1234,
                            ], 'abcd'),
                        ]
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test2_401_apikey',
                    new Request(
                        'GET',
                        '/test/api/key/header',
                        [
                            'api_key' => Error401TestCasesPreparator::FAKE_API_KEY,
                        ]
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test3_401_apikey',
                    new Request(
                        'GET',
                        '/test/api/key/cookie',
                        [
                            'Cookie' => 'api_key=' . Error401TestCasesPreparator::FAKE_API_KEY,
                        ]
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test4_401_apikey',
                    new Request(
                        'GET',
                        '/test/api/key/query?api_key=' . Error401TestCasesPreparator::FAKE_API_KEY
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test5_401_http_basic',
                    new Request(
                        'GET',
                        '/test/basic',
                        [
                            'Authorization' => 'Basic ' . base64_encode('aaaa:bbbbb'),
                        ]
                    ),
                    new Response(401)
                ),
                new TestCase(
                    'test6_401_http_basic',
                    new Request(
                        'GET',
                        '/test/bearer',
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
