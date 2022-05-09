<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Definition\Security\OAuth2\OAuth2ImplicitSecurity;
use APITester\Definition\Token;
use APITester\Preparator\Error403Preparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error403PreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error403Preparator();
        $preparator->addToken(
            new Token(
                'test1',
                'oauth2_implicit',
                '1111',
                [
                    'scope1',
                    'scope2',
                ],
            )
        )
            ->addToken(
                new Token(
                    'test2',
                    'oauth2_implicit',
                    '2222',
                    [
                        'scope3',
                        'scope4',
                    ],
                )
            )
            ->addToken(
                new Token(
                    'test3',
                    'oauth2_implicit',
                    '3333',
                    [
                        'scope5',
                    ],
                )
            )
        ;

        Assert::objectsEqual(
            $expected,
            $preparator->getTestCases($api->getOperations())
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
                        ->addResponse(DefinitionResponse::create(403))
                        ->addSecurity(
                            OAuth2ImplicitSecurity::create(
                                'oauth2_test',
                                'https://petstore3.swagger.io/oauth/authorize',
                            )
                                ->addScopeFromString('scope1')
                                ->addScopeFromString('scope2')
                        )
                ),
            [
                new TestCase(
                    'test1/DeniedToken',
                    new Request(
                        'GET',
                        '/test/oauth2',
                        [
                            'Authorization' => 'Bearer 2222',
                        ]
                    ),
                    new Response(403)
                ),
                new TestCase(
                    'test1/DeniedToken',
                    new Request(
                        'GET',
                        '/test/oauth2',
                        [
                            'Authorization' => 'Bearer 3333',
                        ]
                    ),
                    new Response(403)
                ),
            ],
        ];
    }
}
