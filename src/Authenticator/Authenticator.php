<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Requester\Requester;
use Psr\Http\Client\ClientExceptionInterface;

interface Authenticator
{
    public static function getName(): string;

    /**
     * @throws AuthenticationLoadingException
     * @throws ClientExceptionInterface
     */
    public function authenticate(AuthConfig $config, Api $schema, Requester $requester): ?string;
}
