<?php

declare(strict_types=1);

namespace APITester\Definition;

use APITester\Config\Filters;

final class Token
{
    private readonly string $type;

    private readonly int $expiresIn;

    /**
     * @param string[] $scopes
     */
    public function __construct(
        private readonly string $name,
        private readonly string $authType,
        private readonly string $accessToken,
        private readonly array $scopes = [],
        private readonly ?string $refreshToken = null,
        private readonly ?Filters $filters = null,
        ?string $type = null,
        ?int $expiresIn = null
    ) {
        $this->type = $type ?? 'Bearer';
        $this->expiresIn = $expiresIn ?? 3600;
    }

    public function getFilters(): ?Filters
    {
        return $this->filters;
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
