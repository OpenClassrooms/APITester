<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use OpenAPITesting\Config\Auth;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Token;
use OpenAPITesting\Requester\Requester;

final class OAuth2ImplicitAuthenticator extends Authenticator
{
    public static function getName(): string
    {
        return 'oauth2_implicit';
    }

    /**
     * @inheritdoc
     */
    public function authenticate(Auth $config, Api $api, Requester $requester): Token
    {
        return new Token(self::getName(), '');
    }
}
