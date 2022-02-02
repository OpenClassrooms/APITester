<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class AuthConfig
{
    private string $name;

    private ?string $username;

    private ?string $password;

    private string $type;

    /**
     * @var string[]
     */
    private array $scopes;

    /**
     * @var string[]
     */
    private array $headers;

    /**
     * @param string[]              $scopes
     * @param array<string, string> $headers
     */
    public function __construct(
        string $name,
        string $type,
        ?string $username = null,
        ?string $password = null,
        array $scopes = [],
        array $headers = []
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->type = $type;
        $this->scopes = $scopes;
        $this->name = $name;
        $this->headers = $headers;
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

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
