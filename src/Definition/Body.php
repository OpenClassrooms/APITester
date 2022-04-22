<?php

declare(strict_types=1);

namespace APITester\Definition;

use APITester\Util\Json;
use cebe\openapi\spec\Schema;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

final class Body
{
    private Operation $parent;

    private string $mediaType;

    private Schema $schema;

    private bool $required = true;

    public function __construct(string $mediaType, Schema $schema)
    {
        $this->schema = $schema;
        $this->mediaType = $mediaType;
    }

    public static function create(string $mediaType, Schema $body): self
    {
        return new self($mediaType, $body);
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getParent(): Operation
    {
        return $this->parent;
    }

    public function setParent(Operation $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getStringExample(?string $name = null): string
    {
        $example = $this->parent->getExample($name);

        return $example->getStringBody() ?? Json::encode($this->getRandomContent());
    }

    /**
     * @return mixed[]
     */
    public function getExample(?string $name = null): array
    {
        $example = $this->parent->getExample($name);
        $body = $example->getBody();

        if (null === $body) {
            return $this->getRandomContent();
        }

        return $body
            ->getContent()
        ;
    }

    /**
     * @return mixed[]
     */
    public function getRandomContent(): array
    {
        return (array) (new SchemaFaker(
            $this->getSchema(),
            new Options(),
            true
        ))->generate();
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }
}
