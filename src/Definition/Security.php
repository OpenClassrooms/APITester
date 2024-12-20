<?php

declare(strict_types=1);

namespace APITester\Definition;

use APITester\Definition\Collection\Scopes;

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

    protected Scopes $scopes;

    public function __construct(string $name, ?Scopes $scopes = null)
    {
        $this->name = mb_strtolower($name);
        $this->scopes = $scopes ?? new Scopes();
    }

    final public function getParent(): Operation
    {
        return $this->parent;
    }

    final public function setParent(Operation $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    final public function getDescription(): string
    {
        return $this->description;
    }

    final public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    final public function getName(): string
    {
        return $this->name;
    }

    abstract public function getType(): string;

    final public function getScopes(): Scopes
    {
        return $this->scopes;
    }

    final public function addScopeFromString(string $scope): self
    {
        $this->scopes->add(new Scope($scope));

        return $this;
    }
}
