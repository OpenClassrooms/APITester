<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use cebe\openapi\spec\OAuthFlow;
use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Requester\Requester;
use Psr\Http\Client\ClientExceptionInterface;

abstract class OAuth2Authenticator implements Authenticator
{
    public function authenticate(AuthConfig $config, Api $api, Requester $requester): ?string
    {
        [$authType, $flowType] = explode(':', $config->getType());
        if (0 === \count($api->getSecurities())) {
            throw new \RuntimeException('Auth configured but no security schemes present in api definition');
        }
        foreach ($api->getSecurities() as $scheme) {
            if ($scheme->type === $authType) {
                if (null === $scheme->flows) {
                    throw new \RuntimeException(
                        'Auth configured but no security schemes flows present in api definition'
                    );
                }
                /** @var OAuthFlow|null $flow */
                $flow = $scheme->flows->{$flowType} ?? null;
                if (null === $flow) {
                    throw new AuthenticationLoadingException(
                        "Auth configured but no security schemes flow present for type '{$flowType}' in api definition"
                    );
                }

                return $this->handleFlow($flow, $config, $requester);
            }
        }

        throw new AuthenticationLoadingException('Unable to authenticate');
    }

    /**
     * @throws ClientExceptionInterface
     */
    abstract protected function handleFlow(OAuthFlow $flow, AuthConfig $config, Requester $requester): ?string;
}
