<?php

declare(strict_types=1);

namespace OpenAPITesting\Test;

final class Filters
{
    /**
     * @var string[]
     */
    private array $includedGroups;

    /**
     * @var string[]
     */
    private array $excludedGroups;

    /**
     * @param string[] $includedGroups
     * @param string[] $excludedGroups
     */
    public function __construct(array $includedGroups, array $excludedGroups)
    {
        $this->includedGroups = $includedGroups;
        $this->excludedGroups = $excludedGroups;
    }

    /**
     * @return string[]
     */
    public function getIncludedGroups(): array
    {
        return $this->includedGroups;
    }

    /**
     * @return string[]
     */
    public function getExcludedGroups(): array
    {
        return $this->excludedGroups;
    }

    /**
     * @param string[] $includedGroups
     */
    public function addIncludedGroups(array $includedGroups): void
    {
        /** @var string[] includedGroups */
        $this->includedGroups = [...$includedGroups, ...$this->includedGroups];
    }

    /**
     * @param string[] $excludedGroups
     */
    public function addExcludedGroups(array $excludedGroups): void
    {
        /** @var string[] excludedGroups */
        $this->excludedGroups = [...$excludedGroups, ...$this->excludedGroups];
    }
}