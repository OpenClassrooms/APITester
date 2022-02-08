<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class Filters
{
    /**
     * @var array<array<string, string>>
     */
    private array $include;

    /**
     * @var array<array<string, string>>
     */
    private array $exclude;

    /**
     * @param array<array<string, string>> $include
     * @param array<array<string, string>> $exclude
     */
    public function __construct(?array $include = null, ?array $exclude = null)
    {
        $this->include = $include ?? [];
        $this->exclude = $exclude ?? [];
    }

    /**
     * @return array<array<string, string>>
     */
    public function getInclude(): array
    {
        return $this->include;
    }

    /**
     * @return array<array<string, string>>
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }

    /**
     * @param array<array<string, string>> $include
     */
    public function addInclude(array $include): void
    {
        /** @var array<array<string, string>> includedGroups */
        $this->include = [...$include, ...$this->include];
    }

    /**
     * @param array<array<string, string>> $exclude
     */
    public function addExclude(array $exclude): void
    {
        /** @var array<array<string, string>> excludedGroups */
        $this->exclude = [...$exclude, ...$this->exclude];
    }
}
