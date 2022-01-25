<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Security\OAuth2;

final class ImplicitOAuth2Security extends OAuth2Security
{
    protected string $authorizationUrl;

    /**
     * @param array<string, string> $scopes
     */
    public function __construct(string $name, string $authorizationUrl, array $scopes)
    {
        parent::__construct($name);
        $this->authorizationUrl = $authorizationUrl;
        $this->scopes = $scopes;
    }

    /**
     * @param array<string, string> $scopes
     */
    public static function create(string $name, string $authorizationUrl, array $scopes): self
    {
        return new self($name, $authorizationUrl, $scopes);
    }

    public function getAuthorizationUrl(): string
    {
        return $this->authorizationUrl;
    }
}
