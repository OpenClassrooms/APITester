<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use OpenAPITesting\Authenticator\Exception\AuthenticationException;
use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Config\Auth;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Definition\Token;
use OpenAPITesting\Requester\Requester;

abstract class Authenticator
{
    public const SUPPORTED = [
        'oauth2_password',
        'oauth2_implicit',
    ];

    abstract public static function getName(): string;

    /**
     * @throws AuthenticationException
     * @throws AuthenticationLoadingException
     */
    abstract public function authenticate(Auth $config, Api $api, Requester $requester): Token;

    /**
     * @throws AuthenticationLoadingException
     */
    protected function getSecurity(Api $api, string $type): Security
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
}
