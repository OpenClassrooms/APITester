<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

final class Response
{
    public ?int $statusCode = null;

    public string $body;

    /**
     * @var array<string, string>
     */
    public ?array $headers = null;
}
