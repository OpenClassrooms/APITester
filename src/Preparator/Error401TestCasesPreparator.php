<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use _PHPStan_daf7d5577\Symfony\Component\Console\Exception\LogicException;
use Firebase\JWT\JWT;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Definition\Security\ApiKeySecurity;
use OpenAPITesting\Definition\Security\HttpSecurity;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2Security;

final class Error401TestCasesPreparator extends AuthorisationErrorTestCasesPreparator
{
    public const FAKE_API_KEY = 'b85a985d-0114-4a23-8419-49f64a4c12f8';

    public static function getName(): string
    {
        return '401';
    }

    protected function getStatusCode(): int
    {
        return 401;
    }

    /**
     * @inheritdoc
     */
    protected function getTestTokens(Security $security): array
    {
        if ($security instanceof HttpSecurity && $security->isBasic()) {
            return [base64_encode('aaaa:bbbbb')];
        }

        if ($security instanceof HttpSecurity && $security->isBearer()) {
            return [
                JWT::encode([
                    'test' => 1234,
                ], 'abcd'),
            ];
        }

        if ($security instanceof OAuth2Security) {
            return [
                JWT::encode([
                    'test' => 1234,
                ], 'abcd'),
            ];
        }

        if ($security instanceof ApiKeySecurity) {
            return [self::FAKE_API_KEY];
        }

        throw new LogicException('Unexpected security instance of type ' . \get_class($security));
    }
}
