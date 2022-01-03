<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

use OpenAPITesting\Test\Filters;

final class SuiteConfig
{
    private DefinitionConfig $definition;

    /**
     * @var string[]
     */
    private array $preparators;

    private string $requester;

    private Filters $filters;

    private string $name;

    private ?\Closure $beforeTestCaseCallback;

    private ?\Closure $afterTestCaseCallback;

    /**
     * @param string[] $preparators
     */
    public function __construct(
        string $name,
        DefinitionConfig $definition,
        array $preparators = [],
        string $requester = 'http-async',
        ?Filters $filters = null,
        ?\Closure $beforeTestCaseCallback = null,
        ?\Closure $afterTestCaseCallback = null
    ) {
        $this->name = $name;
        $this->definition = $definition;
        $this->preparators = $preparators;
        $this->filters = $filters ?? new Filters([], []);
        $this->requester = $requester;
        $this->beforeTestCaseCallback = $beforeTestCaseCallback;
        $this->afterTestCaseCallback = $afterTestCaseCallback;
    }

    /**
     * @return string[]
     */
    public function getPreparators(): array
    {
        return $this->preparators;
    }

    public function getDefinition(): DefinitionConfig
    {
        return $this->definition;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilters(): Filters
    {
        return $this->filters;
    }

    /**
     * @param string[] $exclusions
     */
    public function exclude(array $exclusions): self
    {
        $this->filters->addExcludedGroups($exclusions);

        return $this;
    }

    /**
     * @param string[] $inclusions
     */
    public function include(array $inclusions): self
    {
        $this->filters->addIncludedGroups($inclusions);

        return $this;
    }

    public function getRequester(): string
    {
        return $this->requester;
    }

    public function getBeforeTestCaseCallback(): ?\Closure
    {
        return $this->beforeTestCaseCallback;
    }

    public function setBeforeTestCaseCallback(?\Closure $beforeTestCaseCallback): void
    {
        $this->beforeTestCaseCallback = $beforeTestCaseCallback;
    }

    public function getAfterTestCaseCallback(): ?\Closure
    {
        return $this->afterTestCaseCallback;
    }

    public function setAfterTestCaseCallback(?\Closure $afterTestCaseCallback): void
    {
        $this->afterTestCaseCallback = $afterTestCaseCallback;
    }
}
