<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\OpenApi;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2ImplicitSecurity;
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
    public function test(Api $openApi, array $expected): void
    {
        $preparator = new Error403TestCasesPreparator();
        $preparator->setTokens([
            '1111' => [
                'scope1',
                'scope2',
            ],
            '2222' => [
                'scope3',
                'scope4',
            ],
            '3333' => [
                'scope5',
            ],
        ]);
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
            Api::create()
                ->addOperation(
                    Operation::create('test1', '/test/oauth2')
                        ->addResponse(DefinitionResponse::create()->setStatusCode(403))
                        ->addSecurity(
                            OAuth2ImplicitSecurity::create(
                                'oauth2_test',
                                'https://petstore3.swagger.io/oauth/authorize',
                                ['scope2', 'scope1']
                            )
                        )
                )
            ,
            [
                new TestCase(
                    'test1',
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
                    'test1',
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
