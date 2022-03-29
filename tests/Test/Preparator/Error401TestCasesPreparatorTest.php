<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\ParameterExample;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Definition\Security\ApiKeySecurity;
use APITester\Definition\Security\HttpSecurity;
use APITester\Definition\Security\OAuth2\OAuth2ImplicitSecurity;
use APITester\Preparator\Error401TestCasesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use Firebase\JWT\JWT;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error401TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error401TestCasesPreparator();

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api->getOperations()),
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
                    Operation::create('test1', '/test/oauth2/{param}')
                        ->addPathParameter(
                            Parameter::create('param')->addExample(new ParameterExample('first', 'toto'))
                        )
                        ->addResponse(DefinitionResponse::create(401))
                        ->addSecurity(
                            OAuth2ImplicitSecurity::create(
                                'oauth2_test',
                                'https://petstore3.swagger.io/oauth/authorize',
                            )->addScopeFromString('write:pets')
                        )
                )
                ->addOperation(
                    Operation::create('test2', '/test/api/key/header')
                        ->addResponse(DefinitionResponse::create(401))
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
                        ->addResponse(DefinitionResponse::create(401))
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
                        ->addResponse(DefinitionResponse::create(401))
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
                        ->addResponse(DefinitionResponse::create(401))
                        ->addSecurity(
                            HttpSecurity::create(
                                'basic_test',
                                'basic',
                            )
                        )
                )
                ->addOperation(
                    Operation::create('test6', '/test/bearer')
                        ->addResponse(DefinitionResponse::create(401))
                        ->addSecurity(
                            HttpSecurity::create(
                                'bearer_test',
                                'bearer',
                            )
                        )
                ),
            [
                new TestCase(
                    new Request(
                        'GET',
                        '/test/oauth2/toto',
                        [
                            'Authorization' => 'Bearer ' . JWT::encode([
                                'test' => 1234,
                            ], 'abcd'),
                        ]
                    ),
                    new Response(401)
                ),
                new TestCase(
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
                    new Request(
                        'GET',
                        '/test/api/key/query?api_key=' . Error401TestCasesPreparator::FAKE_API_KEY
                    ),
                    new Response(401)
                ),
                new TestCase(
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
