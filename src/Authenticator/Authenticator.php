<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Security;
use OpenAPITesting\Requester\Requester;

abstract class Authenticator
{
    abstract public static function getName(): string;

    /**
     * @throws AuthenticationLoadingException
     */
    abstract public function authenticate(AuthConfig $config, Api $api, Requester $requester): ?string;

    /**
     * @throws AuthenticationLoadingException
     */
    protected function getSecurity(Api $api, string $type): Security
    {
        /** @var Security|null $security */
        $security = $api->getSecurities()
            ->where('type', $type)
        ;
        if (null === $security) {
            throw new AuthenticationLoadingException(
                "Unable to authenticate, security type {$type} not handled but the api."
            );
        }

        return $security;
    }
}
