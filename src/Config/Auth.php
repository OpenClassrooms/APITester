<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class Auth
{
    private string $name;

    /**
     * @var string[]
     */
    private array $headers = [];

    /**
     * @var array<string, string>
     */
    private array $body = [];

    public function __construct(string $name)
    {
        $this->name = $name;
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

    /**
     * @return array<string, string>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @param array<string, string> $body
     */
    public function setBody(array $body): void
    {
        $this->body = $body;
    }
}
