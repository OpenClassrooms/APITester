<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Requester\Requester;

final class OAuth2ImplicitAuthenticator extends Authenticator
{
    public static function getName(): string
    {
        return 'oauth2:implicit';
    }

    public function authenticate(AuthConfig $config, Api $api, Requester $requester): ?string
    {
        return null;
    }
}
