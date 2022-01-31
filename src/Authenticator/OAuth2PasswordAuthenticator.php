<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use Nyholm\Psr7\Request;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2PasswordSecurity;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Json;

final class OAuth2PasswordAuthenticator extends Authenticator
{
    public static function getName(): string
    {
        return 'oauth2:password';
    }

    public function authenticate(AuthConfig $config, Api $api, Requester $requester): ?string
    {
        /** @var OAuth2PasswordSecurity $security */
        $security = $this->getSecurity($api, $config->getType());
        $request = new Request(
            'POST',
            $security->getTokenUrl(),
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
