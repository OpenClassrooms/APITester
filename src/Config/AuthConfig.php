<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class AuthConfig
{
    private ?string $username;

    private ?string $password;

    private string $type;

    /**
     * @var string[]
     */
    private array $scopes;

    /**
     * @param string[] $scopes
     */
    public function __construct(
        string $type,
        ?string $username = null,
        ?string $password = null,
        array $scopes = []
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->type = $type;
        $this->scopes = $scopes;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
