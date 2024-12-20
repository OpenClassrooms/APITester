<?php

declare(strict_types=1);

namespace APITester\Definition;

use cebe\openapi\spec\Schema;

final class Parameter
{
    public const TYPE_QUERY = 'query';

    public const TYPE_PATH = 'path';

    public const TYPE_HEADER = 'header';

    public const TYPES = [self::TYPE_QUERY, self::TYPE_PATH, self::TYPE_HEADER];

    private Operation $parent;

    private string $in;

    private ?Schema $schema;

    /**
     * @param Schema|array<string, string|int>|null $schema
     */
    public function __construct(
        private readonly string $name,
        private bool $required = true,
        $schema = null
    ) {
        if (\is_array($schema)) {
            $schema = new Schema($schema);
        }
        $this->schema = $schema;
    }

    public static function create(string $name, bool $required = true): self
    {
        return new self($name, $required);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function getType(): ?string
    {
        $schema = $this->getSchema();

        if ($schema !== null && $schema->type !== null) {
            return $schema->type;
        }

        return null;
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }

    public function setSchema(?Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    public function getFormat(): ?string
    {
        $schema = $this->getSchema();

        if ($schema !== null && $schema->format !== null) {
            return $schema->format;
        }

        return null;
    }

    public function getExample(?string $name = null): string|int|null
    {
        $example = $this
            ->getParent()
            ->getExample($name)
        ;

        $parameters = $example->getParametersFrom($this->in);
        //        if (!isset($parameters[$this->name])) {
        //            throw new ExampleNotFoundException("Example {$name} not found for parameter {$this->name}.");
        //        }

        return $parameters[$this->name] ?? null;
    }

    public function getParent(): Operation
    {
        return $this->parent;
    }

    public function setParent(Operation $operation): self
    {
        $this->parent = $operation;

        return $this;
    }

    public function setIn(string $in): void
    {
        $this->in = $in;
    }
}
