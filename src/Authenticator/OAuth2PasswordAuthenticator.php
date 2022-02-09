<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use Nyholm\Psr7\Request;
use OpenAPITesting\Authenticator\Exception\AuthenticationException;
use OpenAPITesting\Config\Auth;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2PasswordSecurity;
use OpenAPITesting\Definition\Token;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Json;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

final class OAuth2PasswordAuthenticator extends Authenticator
{
    public static function getName(): string
    {
        return 'oauth2_password';
    }

    /**
     * @inheritdoc
     */
    public function authenticate(Auth $config, Api $api, Requester $requester): Token
    {
        /** @var OAuth2PasswordSecurity $security */
        $security = $this->getSecurity($api, $config->getType());
        $request = $this->buildRequest($security, $config);
        $response = $this->request($requester, $request, $config);
        if (200 !== $response->getStatusCode()) {
            throw new AuthenticationException(
                "Could not authenticate with config {$config->getName()}, 
                statusCode: {$response->getStatusCode()},
                body: {$response->getBody()}",
            );
        }
        /** @var array{'access_token': string, 'refresh_token'?: string, 'token_type'?: string, 'expires_in'?: int} $body */
        $body = Json::decode((string) $response->getBody());

        return new Token(
            self::getName(),
            $body['access_token'],
            $config->getScopes(),
            $body['refresh_token'] ?? null,
            $body['token_type'] ?? null,
            $body['expires_in'] ?? null,
        );
    }

    private function buildRequest(OAuth2PasswordSecurity $security, Auth $config): Request
    {
        return new Request(
            'POST',
            $security->getTokenUrl(),
            $config->getHeaders(),
            Json::encode([
                'grant_type' => 'password',
                'scope' => implode(' ', $config->getScopes()),
                'username' => $config->getUsername(),
                'password' => $config->getPassword(),
            ]),
        );
    }

    /**
     * @throws AuthenticationException
     */
    private function request(Requester $requester, Request $request, Auth $config): ResponseInterface
    {
        $id = uniqid('auth_', false);
        try {
            $requester->request($request, $id);
        } catch (ClientExceptionInterface $e) {
            throw new AuthenticationException(
                "Could not authenticate with config {$config->getName()}",
                0,
                $e
            );
        }

        return $requester->getResponse($id);
    }
}
