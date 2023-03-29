<?php

declare(strict_types=1);

namespace APITester\Definition;

use APITester\Util\Json;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Schema;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

final class Body
{
    private Operation $parent;

    private readonly Schema $schema;

    private bool $required = false;

    /**
     * @param Schema|array<mixed> $schema
     *
     * @throws TypeErrorException
     */
    public function __construct($schema, private readonly string $mediaType = 'application/json')
    {
        $this->schema = $schema instanceof Schema ? $schema : new Schema($schema);
    }

    /**
     * @param Schema|array<mixed> $schema
     *
     * @throws TypeErrorException
     */
    public static function create(\cebe\openapi\spec\Schema|array $schema, string $mediaType = 'application/json'): self
    {
        return new self($schema, $mediaType);
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
