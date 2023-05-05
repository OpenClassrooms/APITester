<?php

declare(strict_types=1);

namespace APITester\Authenticator;

use APITester\Authenticator\Exception\AuthenticationException;
use APITester\Authenticator\Exception\AuthenticationLoadingException;
use APITester\Config\Auth;
use APITester\Definition\Api;
use APITester\Definition\Security;
use APITester\Definition\Security\OAuth2\OAuth2AuthorizationCodeSecurity;
use APITester\Definition\Security\OAuth2\OAuth2ClientCredentialsSecurity;
use APITester\Definition\Security\OAuth2\OAuth2ImplicitSecurity;
use APITester\Definition\Security\OAuth2\OAuth2PasswordSecurity;
use APITester\Definition\Token;
use APITester\Requester\Requester;
use APITester\Util\Json;
use APITester\Util\Random;
use Nyholm\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

final class Authenticator
{
    /**
     * @throws AuthenticationException
     * @throws AuthenticationLoadingException
     */
    public function authenticate(Auth $config, Api $api, Requester $requester): Token
    {
        $security = $this->getSecurity($api, $this->guessType($config));
        $request = $this->buildRequest($security, $config);
        $response = $this->request($requester, $request, $config);
        if ($response->getStatusCode() !== 200) {
            throw new AuthenticationException(
                "Could not authenticate with config {$config->getName()}, 
                statusCode: {$response->getStatusCode()},
                body: {$response->getBody()}",
            );
        }
        /** @var array{'access_token': string, 'refresh_token'?: string, 'token_type'?: string, 'expires_in'?: int} $body */
        $body = Json::decode((string) $response->getBody());

        return new Token(
            $config->getName(),
            $security->getType(),
            $body['access_token'],
            explode(' ', $config->getBody()['scope'] ?? ''),
            $body['refresh_token'] ?? null,
            $body['token_type'] ?? null,
            $body['expires_in'] ?? null,
        );
    }

    /**
     * @throws AuthenticationLoadingException
     */
    private function getSecurity(Api $api, string $type): Security
    {
        /** @var Security|null $security */
        $security = $api->getSecurities()
            ->where('type', $type)
            ->first()
        ;

        if ($security === null) {
            throw new AuthenticationLoadingException(
                "Unable to authenticate, security type {$type} not handled by the defined api."
            );
        }

        return $security;
    }

    private function guessType(Auth $config): string
    {
        if (isset($config->getBody()['grant_type'])) {
            return 'oauth2_' . $config->getBody()['grant_type'];
        }

        throw new \RuntimeException("Impossible to guess security type for {$config->getName()}");
    }

    private function buildRequest(Security $security, Auth $config): Request
    {
        return new Request(
            'POST',
            $this->getRequestUrl($security),
            $config->getHeaders(),
            Json::encode($config->getBody()),
        );
    }

    /**
     * @throws AuthenticationException
     */
    private function request(Requester $requester, Request $request, Auth $config): ResponseInterface
    {
        $id = Random::id('auth_');
        try {
            $requester->request($request, $id);
        } catch (\Throwable $e) {
            throw new AuthenticationException(
                "Could not authenticate with config {$config->getName()}",
                0,
                $e
            );
        }

        return $requester->getResponse($id);
    }

    private function getRequestUrl(Security $security): string
    {
        if ($security instanceof OAuth2ClientCredentialsSecurity
            || $security instanceof OAuth2PasswordSecurity) {
            return $security->getTokenUrl();
        }

        if ($security instanceof OAuth2ImplicitSecurity
            || $security instanceof OAuth2AuthorizationCodeSecurity) {
            return $security->getAuthorizationUrl();
        }

        throw new \RuntimeException("Not supported Authentication for security {$security->getType()}");
    }
}
