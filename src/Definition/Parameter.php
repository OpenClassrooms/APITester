<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\Examples;

final class Parameter
{
    private string $name;

    private string $in;

    private ?Schema $schema;

    private Examples $examples;

    public function __construct(string $name, string $in, ?Schema $schema, ?Examples $examples = null)
    {
        $this->name = $name;
        $this->schema = $schema;
        $this->in = $in;
        $this->examples = $examples ?? new Examples();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }

    public function getIn(): string
    {
        return $this->in;
    }

    public function getExamples(): Examples
    {
        return $this->examples;
    }
}
