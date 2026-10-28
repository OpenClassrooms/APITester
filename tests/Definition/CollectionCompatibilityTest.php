<?php

declare(strict_types=1);

namespace APITester\Tests\Definition;

use APITester\Runtime\Config\Entity\Filters;
use APITester\Schema\Entity\Api;
use APITester\Schema\Entity\Collection\Tags;
use APITester\Schema\Entity\Collection\Tokens;
use APITester\Schema\Entity\Example\OperationExample;
use APITester\Schema\Entity\Operation;
use APITester\Schema\Entity\Parameter;
use APITester\Schema\Entity\Response;
use APITester\Schema\Entity\Security\OAuth2\OAuth2ImplicitSecurity;
use APITester\Schema\Entity\Tag;
use APITester\Schema\Entity\Token;
use PHPUnit\Framework\TestCase;

final class CollectionCompatibilityTest extends TestCase
{
    public function testCollectionsReadComputedPropertiesAndTrackSetterChanges(): void
    {
        $operation = Operation::create('listPets', '/pets', 'get');
        $api = Api::create()->addOperation($operation);

        static::assertSame(['GET'], $api->getOperations()->pluck('method')->all());
        static::assertSame(['/pets'], $api->getOperations()->pluck('path')->all());

        $operation->setMethod('post');

        static::assertSame(['POST'], $api->getOperations()->pluck('method')->all());
        static::assertSame('POST', $operation->getMethod());
    }

    public function testNestedSecuritiesAndScopesAreExtracted(): void
    {
        $security = OAuth2ImplicitSecurity::create('pets', '/oauth/authorize')
            ->addScopeFromString('read:pets');
        $api = Api::create()->addOperation(Operation::create('listPets', '/pets')->addSecurity($security));

        static::assertSame([$security], $api->getSecurities()->values()->all());
        static::assertSame(['read:pets'], $api->getScopes()->pluck('name')->values()->all());
        static::assertSame($security, $api->getSecurities()->firstWhere('type', 'oauth2_implicit'));
    }

    public function testFiltersKeepGetterPathsWildcardsSubstringsAndStrictEquality(): void
    {
        $operation = Operation::create('123', '/pets')
            ->setTags(new Tags([new Tag('Pets')]))
            ->addResponse(Response::create(404))
            ->addQueryParameter(Parameter::create('limit', false));

        static::assertTrue((new Filters([[
            'tags.*.name' => 'Pets',
        ]]))->includes($operation));
        static::assertFalse((new Filters([[
            'tags.*.name' => 'pets',
        ]]))->includes($operation));
        static::assertTrue($operation->has('path', 'pet', 'contains'));
        static::assertTrue($operation->has('responses.*.statusCode', 404));
        static::assertFalse($operation->has('responses.*.statusCode', '404'));
        static::assertFalse($operation->has('id', 123));
        static::assertTrue($operation->has('queryParameters.*.required', false));
        static::assertTrue($operation->has('unknown', null));
    }

    public function testAuthenticationRequiresEveryScopeAndAllowsExtraTokenScopes(): void
    {
        $security = OAuth2ImplicitSecurity::create('pets', '/oauth/authorize')
            ->addScopeFromString('current_user')
            ->addScopeFromString('read:pets')
            ->addScopeFromString('write:pets');
        $operation = Operation::create('createPet', '/pets', 'POST')->addSecurity($security);
        $example = OperationExample::create('createPet', $operation)->setAuthenticationHeaders(new Tokens([
            new Token('partial', 'oauth2_implicit', 'wrong', ['read:pets']),
            new Token('complete', 'oauth2_implicit', 'correct', ['write:pets', 'read:pets', 'other']),
        ]));

        static::assertSame('Bearer correct', $example->getPsrRequest()->getHeaderLine('Authorization'));
    }

    public function testAuthenticationDoesNotSelectATokenMissingARequiredScope(): void
    {
        $operation = Operation::create('createPet', '/pets', 'POST')->addSecurity(
            OAuth2ImplicitSecurity::create('pets', '/oauth/authorize')->addScopeFromString('write:pets')
        );
        $example = OperationExample::create('createPet', $operation)->setAuthenticationHeaders(new Tokens([
            new Token('partial', 'oauth2_implicit', 'wrong', ['read:pets']),
        ]));

        static::assertSame('', $example->getPsrRequest()->getHeaderLine('Authorization'));
    }
}
