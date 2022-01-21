<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\SecuritySchemes;
use OpenAPITesting\Definition\Collection\Servers;
use OpenAPITesting\Definition\Collection\Tags;

final class Api
{
    public ?string $title;

    public ?string $description;

    public ?string $version;

    public Operations $operations;

    public SecuritySchemes $securitySchemes;

    private Servers $servers;

    private Tags $tags;

    public function __construct(
        Operations $operations,
        Servers $servers,
        Tags $tags,
        SecuritySchemes $securitySchemes,
        ?string $title = null,
        ?string $description = null,
        ?string $version = null
    ) {
        $this->operations = $operations;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->servers = $servers;
        $this->tags = $tags;
        $this->securitySchemes = $securitySchemes;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getOperations(array $filters = []): Operations
    {
        return $this->operations;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getServers(): Servers
    {
        return $this->servers;
    }

    public function getTags(): Tags
    {
        return $this->tags;
    }

    public function getSecuritySchemes(): SecuritySchemes
    {
        return $this->securitySchemes;
    }

    /**
     * @return array<string, array<Operation>>
     */
    public function getIndexedOperations(): array
    {
        $operations = [];
        foreach ($this->getOperations() as $operation) {
            $operations[$operation->getMethod()][] = $operation;
            $operations[$operation->getId()][] = $operation;
            foreach ($operation->getTags()->toArray() as $tag) {
                $operations[$tag][] = $operation;
            }
        }

        return $operations;
    }
}
