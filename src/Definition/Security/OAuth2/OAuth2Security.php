<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Security\OAuth2;

use OpenAPITesting\Definition\Security;
use OpenAPITesting\Util\Collection;

abstract class OAuth2Security extends Security
{
    protected ?string $refreshUrl = null;

    /**
     * @var array<string, string>
     */
    protected array $scopes;

    public function getRefreshUrl(): ?string
    {
        return $this->refreshUrl;
    }

    public function setRefreshUrl(?string $refreshUrl): void
    {
        $this->refreshUrl = $refreshUrl;
    }

    /**
     * @return Collection<string, string>
     */
    public function getScopes(): Collection
    {
        return collect($this->scopes);
    }

    public function getType(): string
    {
        return static::TYPE_OAUTH2 . ':' . $this->name;
    }
}
