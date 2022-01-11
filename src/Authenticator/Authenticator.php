<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Requester\Requester;

interface Authenticator
{
    public static function getName(): string;

    /**
     * @throws \OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function authenticate(AuthConfig $config, OpenApi $schema, Requester $requester): ?string;
}
