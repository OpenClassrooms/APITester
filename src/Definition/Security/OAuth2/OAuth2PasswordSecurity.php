<?php

declare(strict_types=1);

namespace APITester\Definition\Security\OAuth2;

use APITester\Definition\Collection\Scopes;

final class OAuth2PasswordSecurity extends OAuth2Security
{
    public function __construct(
        string $name,
        protected string $tokenUrl,
        Scopes $scopes
    ) {
        parent::__construct($name, $scopes);
    }

    public static function create(string $name, string $tokenUrl, Scopes $scopes): self
    {
        return new self($name, $tokenUrl, $scopes);
    }

    public function getTokenUrl(): string
    {
        return $this->tokenUrl;
    }

    public function getType(): string
    {
        return static::TYPE_OAUTH2 . '_password';
    }
}
