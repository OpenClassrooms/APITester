<?php

declare(strict_types=1);

namespace APITester\Config;

use APITester\Requester\HttpAsyncRequester;
use PHPUnit\Framework\TestCase;

final class Suite
{
    private ?string $phpunitConfig = null;

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

    private Filters $filters;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $preparators = [];

    private string $requester;

    private ?string $symfonyKernelClass = null;

    private string $testCaseClass = TestCase::class;

    private ?string $baseUrl = null;

    public function __construct(
        private readonly string $name,
        private readonly Definition $definition
    ) {
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
     * @param \Closure[] $callbacks
     */
    public function setAfterTestCaseCallbacks(array $callbacks): void
    {
        $this->afterTestCaseCallbacks = $callbacks;
    }

    /**
     * @return Auth[]
     */
    public function getAuthentications(): array
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

    /**
     * @param \Closure[] $callbacks
     */
    public function setBeforeTestCaseCallbacks(array $callbacks): void
    {
        $this->beforeTestCaseCallbacks = $callbacks;
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
     * @return array<string, array<string, mixed>>
     */
    public function getPreparators(): array
    {
        return collect($this->preparators)
            ->keyBy('name')
            ->toArray()
        ;
    }

    /**
     * @param array<string, array<string, mixed>> $preparators
     */
    public function setPreparators(array $preparators): void
    {
        $this->preparators = $preparators;
    }

    public function getRequester(): string
    {
        return $this->requester;
    }

    public function setRequester(string $requester): void
    {
        $this->requester = $requester;
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
     * @param array<string, Auth> $auth
     */
    public function setAuth(array $auth): void
    {
        $this->auth = $auth;
    }

    public function getSymfonyKernelClass(): ?string
    {
        return $this->symfonyKernelClass;
    }

    public function setSymfonyKernelClass(string $symfonyKernelClass): void
    {
        $this->symfonyKernelClass = $symfonyKernelClass;
    }

    public function getTestCaseClass(): string
    {
        return $this->testCaseClass;
    }

    public function setTestCaseClass(string $testCaseClass): void
    {
        $this->testCaseClass = $testCaseClass;
    }

    public function getPhpunitConfig(): ?string
    {
        return $this->phpunitConfig;
    }

    public function setPhpunitConfig(?string $phpunitConfig): void
    {
        $this->phpunitConfig = $phpunitConfig;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(?string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }
}
