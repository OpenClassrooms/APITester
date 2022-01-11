<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

use OpenAPITesting\Requester\HttpAsyncRequester;

final class SuiteConfig
{
    private DefinitionConfig $definition;

    private ?AuthConfig $auth;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $preparators;

    private string $requester;

    private FiltersConfig $filters;

    private string $name;

    /**
     * @var \Closure[]
     */
    private array $beforeTestCaseCallbacks;

    /**
     * @var \Closure[]
     */
    private array $afterTestCaseCallbacks;

    /**
     * @param \Closure[]                          $beforeTestCaseCallbacks
     * @param \Closure[]                          $afterTestCaseCallbacks
     * @param array<string, array<string, mixed>> $preparators
     */
    public function __construct(
        string $name,
        DefinitionConfig $definition,
        array $preparators = [],
        ?string $requester = null,
        ?AuthConfig $auth = null,
        ?FiltersConfig $filters = null,
        array $beforeTestCaseCallbacks = [],
        array $afterTestCaseCallbacks = []
    ) {
        $this->name = $name;
        $this->definition = $definition;
        $this->preparators = $preparators;
        $this->auth = $auth;
        $this->filters = $filters ?? new FiltersConfig([], []);
        $this->requester = $requester ?? HttpAsyncRequester::getName();
        $this->beforeTestCaseCallbacks = $beforeTestCaseCallbacks;
        $this->afterTestCaseCallbacks = $afterTestCaseCallbacks;
    }

    /**
     * @return array<string, array<string, mixed>>
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

    public function getFilters(): FiltersConfig
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

    public function getAuth(): ?AuthConfig
    {
        return $this->auth;
    }
}
