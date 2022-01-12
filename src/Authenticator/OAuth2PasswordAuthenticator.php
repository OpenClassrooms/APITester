<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use cebe\openapi\spec\OAuthFlow;
use Nyholm\Psr7\Request;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Json;

final class OAuth2PasswordAuthenticator extends OAuth2Authenticator
{
    public static function getName(): string
    {
        return 'oauth2:password';
    }

    protected function handleFlow(OAuthFlow $flow, AuthConfig $config, Requester $requester): ?string
    {
        $request = new Request(
            'POST',
            $flow->tokenUrl,
            [],
            Json::encode([
                'username' => $config->getUsername(),
                'password' => $config->getPassword(),
            ]),
        );
        $id = uniqid('auth_', false);
        $requester->request($request, $id);
        $response = $requester->getResponse($id);

        return (string) Json::decode((string) $response->getBody())['token'];
    }
}
