<?php

declare(strict_types=1);

namespace APITester\Definition\Security\OAuth2;

use APITester\Definition\Collection\Scopes;

final class OAuth2AuthorizationCodeSecurity extends OAuth2Security
{
    private string $tokenUrl;

    private string $authorizationUrl;

    public function __construct(
        string $name,
        string $authorizationUrl,
        string $tokenUrl,
        Scopes $scopes
    ) {
        parent::__construct($name, $scopes);
        $this->tokenUrl = $tokenUrl;
        $this->authorizationUrl = $authorizationUrl;
        $this->scopes = $scopes;
    }

    public static function create(
        string $name,
        string $authorizationUrl,
        string $tokenUrl,
        Scopes $scopes
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

    public function getType(): string
    {
        return static::TYPE_OAUTH2 . '_authorization_code';
    }
}
