<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

use OpenAPITesting\Requester\HttpAsyncRequester;

final class Suite
{
    /**
     * @var \Closure[]
     */
    private array $afterTestCaseCallbacks = [];

    /**
     * @var Auth[]
     */
    private array $auth = [];

    /**
     * @var \Closure[]
     */
    private array $beforeTestCaseCallbacks = [];

    private Definition $definition;

    private Filters $filters;

    private string $name;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $preparators = [];

    private string $requester;

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

    public function addAfterTestCaseCallback(\Closure $callback): void
    {
        $this->afterTestCaseCallbacks[] = $callback;
    }

    public function addBeforeTestCaseCallback(\Closure $callback): void
    {
        $this->beforeTestCaseCallbacks[] = $callback;
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
     * @return \Closure[]
     */
    public function getAfterTestCaseCallbacks(): array
    {
        return $this->afterTestCaseCallbacks;
    }

    /**
     * @return Auth[]
     */
    public function getAuthentifications(): array
    {
        return $this->auth;
    }

    /**
     * @return \Closure[]
     */
    public function getBeforeTestCaseCallbacks(): array
    {
        return $this->beforeTestCaseCallbacks;
    }

    public function getDefinition(): Definition
    {
        return $this->definition;
    }

    public function getFilters(): Filters
    {
        return $this->filters;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getPreparators(): array
    {
        return $this->preparators;
    }

    public function getRequester(): string
    {
        return $this->requester;
    }

    /**
     * @param array<array<string, string>> $inclusions
     */
    public function include(array $inclusions): self
    {
        $this->filters->addInclude($inclusions);

        return $this;
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setAfterTestCaseCallbacks(array $callbacks): void
    {
        $this->afterTestCaseCallbacks = $callbacks;
    }

    /**
     * @param array<string, Auth> $auth
     */
    public function setAuth(array $auth): void
    {
        $this->auth = $auth;
    }

    /**
     * @param \Closure[] $callbacks
     */
    public function setBeforeTestCaseCallbacks(array $callbacks): void
    {
        $this->beforeTestCaseCallbacks = $callbacks;
    }

    public function setFilters(Filters $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @param array<string, array<string, mixed>> $preparators
     */
    public function setPreparators(array $preparators): void
    {
        $this->preparators = $preparators;
    }

    public function setRequester(string $requester): void
    {
        $this->requester = $requester;
    }
}
