<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2ImplicitSecurity;
use OpenAPITesting\Definition\Token;
use OpenAPITesting\Preparator\Error403TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

final class Error403TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error403TestCasesPreparator();
        $preparator->addToken(
            new Token(
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
                    Operation::create('test1', '/test/oauth2')
                        ->addResponse(DefinitionResponse::create()->setStatusCode(403))
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
                    'test1_403_oauth2_implicit',
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
                    'test1_403_oauth2_implicit',
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
