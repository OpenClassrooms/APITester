<?php

declare(strict_types=1);

namespace APITester\Definition;

final class Token
{
    private string $type;

    private string $authType;

    private int $expiresIn;

    private string $accessToken;

    private ?string $refreshToken;

    /**
     * @var string[]
     */
    private array $scopes;

    private string $name;

    /**
     * @param string[] $scopes
     */
    public function __construct(
        string $name,
        string $authType,
        string $accessToken,
        array $scopes = [],
        ?string $refreshToken = null,
        ?string $type = null,
        ?int $expiresIn = null
    ) {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->type = $type ?? 'Bearer';
        $this->expiresIn = $expiresIn ?? 3600;
        $this->scopes = $scopes;
        $this->authType = $authType;
        $this->name = $name;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
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

    public function getAuthType(): string
    {
        return $this->authType;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
