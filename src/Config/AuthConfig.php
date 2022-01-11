<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class AuthConfig
{
    private ?string $username;

    private ?string $password;

    private string $type;

    public function __construct(string $type, ?string $username = null, ?string $password = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->type = $type;
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
}
