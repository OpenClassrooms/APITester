<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use OpenAPITesting\Definition\Security\ApiKeySecurity;
use OpenAPITesting\Definition\Security\HttpSecurity;
use OpenAPITesting\Definition\Security\OAuth2\OAuth2Security;

abstract class Security
{
    public const TYPE_HTTP_BASIC = 'http_basic';

    public const TYPE_HTTP_BEARER = 'http_basic';

    public const TYPE_OAUTH2 = 'oauth2';

    public const TYPE_API_KEY = 'apikey';

    public const SCHEME_BASIC_AUTH = 'basic';

    public const SCHEME_BEARER_AUTH = 'bearer';

    protected Operation $operation;

    protected string $description = '';

    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function setOperation(Operation $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        if ($this instanceof HttpSecurity && $this->getScheme() === static::SCHEME_BASIC_AUTH) {
            return static::TYPE_HTTP_BASIC;
        }

        if ($this instanceof HttpSecurity && $this->getScheme() === static::SCHEME_BEARER_AUTH) {
            return static::TYPE_HTTP_BEARER;
        }

        if ($this instanceof ApiKeySecurity) {
            return static::TYPE_API_KEY;
        }

        if ($this instanceof OAuth2Security) {
            return static::TYPE_OAUTH2;
        }
        $type = static::class;
        throw new \RuntimeException("Unhandled security type for class {$type}");
    }
}
