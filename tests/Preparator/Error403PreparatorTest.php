<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Schema\Entity\Api;
use APITester\Schema\Entity\Example\OperationExample;
use APITester\Schema\Entity\Example\ResponseExample;
use APITester\Schema\Entity\Operation;
use APITester\Schema\Entity\Response as DefinitionResponse;
use APITester\Schema\Entity\Security\OAuth2\OAuth2ImplicitSecurity;
use APITester\Schema\Entity\Token;
use APITester\Test\Entity\TestCase;
use APITester\Test\Preparator\Error403Preparator;
use APITester\Util\Assert;

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
            $preparator->doPrepare($api->getOperations()),
            ['parent']
        );
    }

    /**
     * @return iterable<int, array{Api, array<TestCase>}>
     */
    public static function getData(): iterable
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
                    Error403Preparator::getName() . ' - test1 - DeniedToken',
                    OperationExample::create('test1')
                        ->setPath('/test/oauth2')
                        ->setHeaders([
                            'Authorization' => 'Bearer 2222',
                        ])
                        ->setResponse(ResponseExample::create('403')),
                ),
                new TestCase(
                    Error403Preparator::getName() . ' - test1 - DeniedToken',
                    OperationExample::create('test1')
                        ->setPath('/test/oauth2')
                        ->setHeaders([
                            'Authorization' => 'Bearer 3333',
                        ])
                        ->setResponse(ResponseExample::create('403')),
                ),
            ],
        ];

        yield [
            Api::create()
                ->addOperation(
                    Operation::create('testNoInvalidScopeToken', '/test/oauth2/no-invalid-token')
                        ->addResponse(DefinitionResponse::create(403))
                        ->addSecurity(
                            OAuth2ImplicitSecurity::create(
                                'oauth2_test_no_invalid_scope_token',
                                'https://petstore3.swagger.io/oauth/authorize',
                            )
                                ->addScopeFromString('scope1')
                                ->addScopeFromString('scope3')
                                ->addScopeFromString('scope5')
                        )
                ),
            [],
        ];
    }
}
