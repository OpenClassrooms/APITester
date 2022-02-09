<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class Auth
{
    private string $name;

    private ?string $username = null;

    private ?string $password = null;

    private string $type;

    /**
     * @var string[]
     */
    private array $scopes = [];

    /**
     * @var string[]
     */
    private array $headers = [];

    public function __construct(string $name, string $type)
    {
        $this->type = $type;
        $this->name = $name;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
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

    /**
     * @param string[] $scopes
     */
    public function setScopes(array $scopes): void
    {
        $this->scopes = $scopes;
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

    /**
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}
