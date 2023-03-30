<?php

declare(strict_types=1);

namespace APITester\Definition;

final class Server
{
    public function __construct(
        private readonly string $url
    ) {
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
