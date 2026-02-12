<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Definition\Security\ApiKeySecurity;
use APITester\Definition\Security\HttpSecurity;
use APITester\Definition\Security\OAuth2\OAuth2ImplicitSecurity;
use APITester\Preparator\Error401Preparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use Firebase\JWT\JWT;

final class Error401PreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error401Preparator();

        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['parent']
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
                            Parameter::create('param')
                        )
                        ->addResponse(DefinitionResponse::create(401))
                        ->addSecurity(
                            OAuth2ImplicitSecurity::create(
                                'oauth2_test',
                                'https://petstore3.swagger.io/oauth/authorize',
                            )->addScopeFromString('write:pets')
                        )
                        ->addExample(
                            OperationExample::create('default')
                                ->setPathParameters([
                                    'param' => 'toto',
                                ])
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
                    Error401Preparator::getName() . ' - test1 - InvalidToken',
                    OperationExample::create('test1')
                        ->setPath('/test/oauth2/toto')
                        ->setHeaders([
                            'Authorization' => 'Bearer ' . JWT::encode([
                                'test' => 1234,
                            ], 'abcd', Error401Preparator::ALG),
                        ])
                        ->setResponse(ResponseExample::create('401'))
                ),
                new TestCase(
                    Error401Preparator::getName() . ' - test2 - InvalidToken',
                    OperationExample::create('test1')
                        ->setPath('/test/api/key/header')
                        ->setHeaders([
                            'api_key' => Error401Preparator::FAKE_API_KEY,
                        ])
                        ->setResponse(ResponseExample::create('401'))
                ),
                new TestCase(
                    Error401Preparator::getName() . ' - test3 - InvalidToken',
                    OperationExample::create('test3')
                        ->setPath('/test/api/key/cookie')
                        ->setHeaders([
                            'Cookie' => 'api_key=' . Error401Preparator::FAKE_API_KEY,
                        ])
                        ->setResponse(ResponseExample::create('401'))
                ),
                new TestCase(
                    Error401Preparator::getName() . ' - test4 - InvalidToken',
                    OperationExample::create('test4')
                        ->setPath('/test/api/key/query?api_key=' . Error401Preparator::FAKE_API_KEY)
                        ->setResponse(ResponseExample::create('401'))
                ),
                new TestCase(
                    Error401Preparator::getName() . ' - test5 - InvalidToken',
                    OperationExample::create('test5')
                        ->setPath('/test/basic')
                        ->setHeaders([
                            'Authorization' => 'Basic ' . base64_encode('aaaa:bbbbb'),
                        ])
                        ->setResponse(ResponseExample::create('401'))
                ),
                new TestCase(
                    Error401Preparator::getName() . ' - test6 - InvalidToken',
                    OperationExample::create('test5')
                        ->setPath('/test/bearer')
                        ->setHeaders([
                            'Authorization' => 'Bearer ' . JWT::encode([
                                'test' => 1234,
                            ], 'abcd', Error401Preparator::ALG),
                        ])
                        ->setResponse(ResponseExample::create('401'))
                ),
            ],
        ];
    }
}
