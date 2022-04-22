<?php

declare(strict_types=1);

namespace APITester\Definition\Security\OAuth2;

use APITester\Definition\Security;

abstract class OAuth2Security extends Security
{
    protected ?string $refreshUrl = null;

    public function getRefreshUrl(): ?string
    {
        return $this->refreshUrl;
    }

    public function setRefreshUrl(?string $refreshUrl): void
    {
        $this->refreshUrl = $refreshUrl;
    }
}