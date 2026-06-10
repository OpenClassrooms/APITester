<?php

declare(strict_types=1);

namespace APITester\Test\Preparator;

use APITester\Schema\Entity\Collection\Tokens;
use APITester\Schema\Entity\Security;
use APITester\Schema\Entity\Security\ApiKeySecurity;
use APITester\Schema\Entity\Security\HttpSecurity;
use APITester\Schema\Entity\Security\OAuth2\OAuth2Security;
use APITester\Schema\Entity\Token;
use Firebase\JWT\JWT;

final class Error401Preparator extends SecurityErrorPreparator
{
    public const FAKE_API_KEY = 'b85a985d-0114-4a23-8419-49f64a4c12f8';

    public const ALG = 'HS256';

    protected function getStatusCode(): string
    {
        return '401';
    }

    protected function getTestTokens(Security $security): Tokens
    {
        $tokens = new Tokens();
        if ($security instanceof HttpSecurity && $security->isBasic()) {
            $tokens->add(
                new Token(
                    '401_false_token',
                    $security->getType(),
                    base64_encode('aaaa:bbbbb')
                )
            );
        }

        if ($security instanceof HttpSecurity && $security->isBearer()) {
            $tokens->add(
                new Token(
                    '401_false_token',
                    $security->getType(),
                    JWT::encode([
                        'test' => 1234,
                    ], hash('sha256', 'abcd'), self::ALG)
                ),
            );
        }

        if ($security instanceof OAuth2Security) {
            $tokens->add(
                new Token(
                    '401_false_token',
                    $security->getType(),
                    JWT::encode([
                        'test' => 1234,
                    ], hash('sha256', 'abcd'), self::ALG)
                ),
            );
        }

        if ($security instanceof ApiKeySecurity) {
            $tokens->add(
                new Token(
                    '401_false_token',
                    $security->getType(),
                    self::FAKE_API_KEY
                )
            );
        }

        return $tokens;
    }

    protected function getTestCaseName(): string
    {
        return 'InvalidToken';
    }
}
