<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Security\OAuth2;

final class OAuth2AuthorizationCodeSecurity extends OAuth2Security
{
    private string $tokenUrl;

    private string $authorizationUrl;

    /**
     * @param array<string, string> $scopes
     */
    public function __construct(
        string $name,
        string $authorizationUrl,
        string $tokenUrl,
        array $scopes
    ) {
        parent::__construct($name);
        $this->tokenUrl = $tokenUrl;
        $this->authorizationUrl = $authorizationUrl;
        $this->scopes = $scopes;
    }

    /**
     * @param array<string, string> $scopes
     */
    public static function create(
        string $name,
        string $authorizationUrl,
        string $tokenUrl,
        array $scopes
    ): self {
        return new self($name, $authorizationUrl, $tokenUrl, $scopes);
    }

    public function getTokenUrl(): string
    {
        return $this->tokenUrl;
    }

    public function getAuthorizationUrl(): string
    {
        return $this->authorizationUrl;
    }
}
