<?php

declare(strict_types=1);

namespace APITester\Definition\Security;

use APITester\Definition\Collection\Scopes;
use APITester\Definition\Security;

final class ApiKeySecurity extends Security
{
    private string $in;

    private string $keyName;

    public function __construct(string $name, string $keyName, string $in, ?Scopes $scopes = null)
    {
        parent::__construct($name, $scopes);
        $this->in = $in;
        $this->keyName = $keyName;
    }

    public static function create(string $name, string $key, string $in, ?Scopes $scopes = null): self
    {
        return new self($name, $key, $in, $scopes);
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

    public function getType(): string
    {
        return static::TYPE_API_KEY;
    }
}
