<?php

declare(strict_types=1);

namespace APITester\Definition\Security;

use APITester\Definition\Collection\Scopes;
use APITester\Definition\Security;

final class HttpSecurity extends Security
{
    private string $scheme;

    private ?string $format;

    public function __construct(string $name, string $scheme, ?string $format = null, ?Scopes $scopes = null)
    {
        parent::__construct($name, $scopes);
        $this->scheme = $scheme;
        $this->format = $format;
    }

    public static function create(string $name, string $scheme, ?string $format = null, ?Scopes $scopes = null): self
    {
        return new self($name, $scheme, $format, $scopes);
    }

    public function isBasic(): bool
    {
        return Security::SCHEME_BASIC_AUTH === $this->getScheme();
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function isBearer(): bool
    {
        return Security::SCHEME_BEARER_AUTH === $this->getScheme();
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getType(): string
    {
        if ($this->getScheme() === static::SCHEME_BASIC_AUTH) {
            return static::TYPE_HTTP_BASIC;
        }

        if ($this->getScheme() === static::SCHEME_BEARER_AUTH) {
            return static::TYPE_HTTP_BEARER;
        }

        throw new \RuntimeException("Unhandled security scheme '{$this->getScheme()}'.");
    }
}
