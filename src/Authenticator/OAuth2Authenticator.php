<?php

declare(strict_types=1);

namespace OpenAPITesting\Authenticator;

use cebe\openapi\spec\OAuthFlow;
use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Authenticator\Exception\AuthenticationLoadingException;
use OpenAPITesting\Config\AuthConfig;
use OpenAPITesting\Requester\Requester;

abstract class OAuth2Authenticator implements Authenticator
{
    public function authenticate(AuthConfig $config, OpenApi $schema, Requester $requester): ?string
    {
        [$authType, $flowType] = explode(':', $config->getType());
        /** @var \cebe\openapi\spec\SecurityScheme[] $securitySchemes */
        $securitySchemes = $schema->components->securitySchemes ?? [];
        if (0 === \count($securitySchemes)) {
            throw new \RuntimeException('Auth configured but no security schemes present in api definition');
        }
        foreach ($securitySchemes as $scheme) {
            if ($scheme->type === $authType) {
                if (null === $scheme->flows) {
                    throw new \RuntimeException(
                        'Auth configured but no security schemes flows present in api definition'
                    );
                }
                /** @var \cebe\openapi\spec\OAuthFlow|null $flow */
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
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    abstract protected function handleFlow(OAuthFlow $flow, AuthConfig $config, Requester $requester): ?string;
}
