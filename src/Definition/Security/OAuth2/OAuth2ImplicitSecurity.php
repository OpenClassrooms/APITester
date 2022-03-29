<?php

declare(strict_types=1);

namespace APITester\Definition\Security\OAuth2;

use APITester\Definition\Collection\Scopes;

final class OAuth2ImplicitSecurity extends OAuth2Security
{
    protected string $authorizationUrl;

    public function __construct(string $name, string $authorizationUrl, ?Scopes $scopes = null)
    {
        parent::__construct($name, $scopes);
        $this->authorizationUrl = $authorizationUrl;
    }

    public static function create(string $name, string $authorizationUrl, ?Scopes $scopes = null): self
    {
        return new self($name, $authorizationUrl, $scopes);
    }

    public function getAuthorizationUrl(): string
    {
        return $this->authorizationUrl;
    }

    public function getType(): string
    {
        return static::TYPE_OAUTH2 . '_implicit';
    }
}
