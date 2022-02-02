<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Security\OAuth2;

final class OAuth2PasswordSecurity extends OAuth2Security
{
    protected string $tokenUrl;

    /**
     * @param string[] $scopes
     */
    public function __construct(string $name, string $tokenUrl, array $scopes)
    {
        parent::__construct($name, $scopes);
        $this->tokenUrl = $tokenUrl;
    }

    /**
     * @param array<string, string> $scopes
     */
    public static function create(string $name, string $tokenUrl, array $scopes): self
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
