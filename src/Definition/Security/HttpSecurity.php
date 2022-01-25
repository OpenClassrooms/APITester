<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Security;

use OpenAPITesting\Definition\Security;

final class HttpSecurity extends Security
{
    private string $scheme;

    private ?string $format;


    public function __construct(string $name, string $scheme, ?string $format = null)
    {
        parent::__construct($name);
        $this->scheme = $scheme;
        $this->format = $format;
    }

    public static function create(string $name, string $scheme, ?string $format = null): self
    {
        return new self($name, $scheme, $format);
    }

    public function isBasic(): bool
    {
        return $this->getScheme() === Security::SCHEME_BASIC_AUTH;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function isBearer(): bool
    {
        return $this->getScheme() === Security::SCHEME_BEARER_AUTH;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }
}
