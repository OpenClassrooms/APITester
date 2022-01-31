<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Securities;
use OpenAPITesting\Definition\Collection\Servers;
use OpenAPITesting\Definition\Collection\Tags;

final class Api
{
    private ?string $title;

    private ?string $description;

    private ?string $version;

    private Operations $operations;

    private Servers $servers;

    private Tags $tags;

    public function __construct()
    {
        $this->operations = new Operations();
        $this->servers = new Servers();
        $this->tags = new Tags();
    }

    public static function create(): self
    {
        return new self();
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    public function addOperation(Operation $operation): self
    {
        $operation->setParent($this);
        $this->operations->add($operation);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getServers(): Servers
    {
        return $this->servers;
    }

    public function setServers(Servers $servers): self
    {
        $this->servers = $servers;

        return $this;
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
            foreach ($operation->getTags()->select('name') as $tag) {
                $operations[(string) $tag][] = $operation;
            }
        }

        return $operations;
    }

    public function getOperations(): Operations
    {
        return $this->operations;
    }

    public function setOperations(Operations $operations): self
    {
        foreach ($operations as $operation) {
            $operation->setParent($this);
        }
        $this->operations = $operations;

        return $this;
    }

    public function getTags(): Tags
    {
        return $this->tags;
    }

    public function setTags(Tags $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return Collection<string>
     */
    public function getScopes(): Collection
    {
        return $this->getSecurities()
            ->select('scopes')
            ->flatten()
            ->unique()
        ;
    }

    public function getSecurities(): Securities
    {
        /** @var Securities $securities */
        return $this->getOperations()
            ->select('securities.*')
            ->flatten()
        ;
    }
}
