<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class SuiteConfig
{
    private DefinitionConfig $definition;

    /**
     * @var string[]
     */
    private array $preparators;

    /**
     * @var string[]
     */
    private array $groups;

    private string $title;

    /**
     * @param string[] $preparators
     * @param string[] $groups
     */
    public function __construct(string $title, DefinitionConfig $definition, array $preparators, array $groups)
    {
        $this->title = $title;
        $this->definition = $definition;
        $this->preparators = $preparators;
        $this->groups = $groups;
    }

    /**
     * @return string[]
     */
    public function getPreparators(): array
    {
        return $this->preparators;
    }

    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getDefinition(): DefinitionConfig
    {
        return $this->definition;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
