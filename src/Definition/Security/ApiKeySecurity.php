<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Security;

use OpenAPITesting\Definition\Security;

final class ApiKeySecurity extends Security
{
    private string $in;

    private string $keyName;

    public function __construct(string $name, string $keyName, string $in)
    {
        parent::__construct($name);
        $this->in = $in;
        $this->keyName = $keyName;
    }

    public static function create(string $name, string $key, string $in): self
    {
        return new self($name, $key, $in);
    }

    public function getIn(): string
    {
        return $this->in;
    }

    public function getKeyName(): string
    {
        return $this->keyName;
    }

    public function getKey(string $value): string
    {
        if ('cookie' === $this->in) {
            return "{$this->keyName}={$value}";
        }

        if ('query' === $this->in) {
            return "&{$this->keyName}={$value}";
        }

        return $this->keyName;
    }
}
