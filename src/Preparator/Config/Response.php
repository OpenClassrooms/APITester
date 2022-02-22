<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator\Config;

use cebe\openapi\spec\Schema;

final class Response
{
    public ?int $statusCode = null;

    /** @var mixed|Schema */
    public $body;

    /**
     * @var array<string, string>
     */
    public ?array $headers = null;
}
