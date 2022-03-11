<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use Nyholm\Psr7\Request;
use OpenAPITesting\Authenticator\Exception\AuthenticationException;
use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Config\Auth;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2AuthorizationCodeSecurity;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2ClientCredentialsSecurity;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2ImplicitSecurity;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2PasswordSecurity;
use OpenAPITesting\Definition\Token;
use OpenAPITesting\Requester\Requester;
use OpenAPITesting\Util\Json;
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
            explode(' ', $config->getBody()['scope'] ?? ''),
            $body['refresh_token'] ?? null,
            $body['token_type'] ?? null,
            $body['expires_in'] ?? null,
        );
    }

    public static function getName(): string
    {
        return mb_strtolower(
            str_replace(
                (new \ReflectionClass(self::class))->getShortName(),
                '',
                (new \ReflectionClass(static::class))->getShortName()
            )
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

        if (null === $security) {
            throw new AuthenticationLoadingException(
                "Unable to authenticate, security type {$type} not handled but the defined api."
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
        $id = uniqid('auth_', false);
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
