<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Firebase\JWT\JWT;
use OpenAPITesting\Definition\Collection\Tokens;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Definition\Security\ApiKeySecurity;
use OpenAPITesting\Definition\Security\HttpSecurity;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2Security;
use OpenAPITesting\Definition\Token;

final class Error401TestCasesPreparator extends AuthorisationErrorTestCasesPreparator
{
    public const FAKE_API_KEY = 'b85a985d-0114-4a23-8419-49f64a4c12f8';

    protected function getStatusCode(): int
    {
        return 401;
    }

    protected function getTestTokens(Security $security): Tokens
    {
        $tokens = new Tokens();
        if ($security instanceof HttpSecurity && $security->isBasic()) {
            $tokens->add(
                new Token(
                    $security->getType(),
                    base64_encode('aaaa:bbbbb')
                )
            );
        }

        if ($security instanceof HttpSecurity && $security->isBearer()) {
            $tokens->add(
                new Token(
                    $security->getType(),
                    JWT::encode([
                        'test' => 1234,
                    ], 'abcd')
                ),
            );
        }

        if ($security instanceof OAuth2Security) {
            $tokens->add(
                new Token(
                    $security->getType(),
                    JWT::encode([
                        'test' => 1234,
                    ], 'abcd')
                ),
            );
        }

        if ($security instanceof ApiKeySecurity) {
            $tokens->add(
                new Token(
                    $security->getType(),
                    self::FAKE_API_KEY
                )
            );
        }

        return $tokens;
    }
}
