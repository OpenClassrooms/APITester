<?php

declare(strict_types=1);

namespace APITester\Schema\Entity\Security\OAuth2;

use APITester\Schema\Entity\Security;

abstract class OAuth2Security extends Security
{
    protected ?string $refreshUrl = null;

    final public function getRefreshUrl(): ?string
    {
        return $this->refreshUrl;
    }

    final public function setRefreshUrl(?string $refreshUrl): void
    {
        $this->refreshUrl = $refreshUrl;
    }
}
