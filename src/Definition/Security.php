<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

abstract class Security
{
    public const TYPE_HTTP_BASIC = 'http_basic';

    public const TYPE_HTTP_BEARER = 'http_basic';

    public const TYPE_OAUTH2 = 'oauth2';

    public const TYPE_API_KEY = 'apikey';

    public const SCHEME_BASIC_AUTH = 'basic';

    public const SCHEME_BEARER_AUTH = 'bearer';

    protected Operation $parent;

    protected string $description = '';

    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getParent(): Operation
    {
        return $this->parent;
    }

    public function setParent(Operation $parent): self
    {
        $this->parent = $parent;

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

    abstract public function getType(): string;
}
