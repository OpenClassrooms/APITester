<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

use OpenAPITesting\Requester\HttpAsyncRequester;

final class Suite
{
    private Definition $definition;

    /**
     * @var Auth[]
     */
    private array $auth = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $preparators = [];

    private string $requester;

    private Filters $filters;

    private string $name;

    /**
     * @var \Closure[]
     */
    private array $beforeTestCaseCallbacks = [];

    /**
     * @var \Closure[]
     */
    private array $afterTestCaseCallbacks = [];

    public function __construct(string $name, Definition $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
        $this->filters = new Filters();
        $this->requester = HttpAsyncRequester::getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getPreparators(): array
    {
        return $this->preparators;
    }

    /**
     * @param array<string, array<string, mixed>> $preparators
     */
    public function setPreparators(array $preparators): void
    {
        $this->preparators = $preparators;
    }

    public function getDefinition(): Definition
    {
        return $this->definition;
    }

    public function getFilters(): Filters
    {
        return $this->filters;
    }

    public function setFilters(Filters $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @param array<array<string, string>> $exclusions
     */
    public function exclude(array $exclusions): self
    {
        $this->filters->addExclude($exclusions);

        return $this;
    }

    /**
     * @param array<array<string, string>> $inclusions
     */
    public function include(array $inclusions): self
    {
        $this->filters->addInclude($inclusions);

        return $this;
    }

    public function getRequester(): string
    {
        return $this->requester;
    }

    /**
     * @return \Closure[]
     */
    public function getBeforeTestCaseCallbacks(): array
    {
        return $this->beforeTestCaseCallbacks;
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setBeforeTestCaseCallbacks(array $callbacks): void
    {
        $this->beforeTestCaseCallbacks = $callbacks;
    }

    /**
     * @return \Closure[]
     */
    public function getAfterTestCaseCallbacks(): array
    {
        return $this->afterTestCaseCallbacks;
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setAfterTestCaseCallbacks(array $callbacks): void
    {
        $this->afterTestCaseCallbacks = $callbacks;
    }

    /**
     * @return Auth[]
     */
    public function getAuthentifications(): array
    {
        return $this->auth;
    }

    /**
     * @param array<string, Auth> $auth
     */
    public function setAuth(array $auth): void
    {
        $this->auth = $auth;
    }

    public function addBeforeTestCaseCallback(\Closure $callback): void
    {
        $this->beforeTestCaseCallbacks[] = $callback;
    }

    public function addAfterTestCaseCallback(\Closure $callback): void
    {
        $this->afterTestCaseCallbacks[] = $callback;
    }
}
