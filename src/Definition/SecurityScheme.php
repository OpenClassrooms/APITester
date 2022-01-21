<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\OAuthFlows;

final class SecurityScheme
{
    public string $type;

    public ?OAuthFlows $flows;

    public function __construct(string $type, ?OAuthFlows $flows = null)
    {
        $this->flows = $flows;
        $this->type = $type;
    }

    public function getFlows(): ?OAuthFlows
    {
        return $this->flows;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
