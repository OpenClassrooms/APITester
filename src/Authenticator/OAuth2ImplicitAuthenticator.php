<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use cebe\openapi\spec\OAuthFlow;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Requester\Requester;

final class OAuth2ImplicitAuthenticator extends OAuth2Authenticator
{
    public static function getName(): string
    {
        return 'oauth2:implicit';
    }

    protected function handleFlow(OAuthFlow $flow, AuthConfig $config, Requester $requester): ?string
    {
        return null;
    }
}
