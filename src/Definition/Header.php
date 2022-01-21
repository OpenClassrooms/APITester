<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Collection\Examples;

final class Header
{
    private ?string $name;

    private ?Schema $schema;

    private Examples $examples;

    public function __construct(?string $name, ?Schema $schema, ?Examples $examples = null)
    {
        $this->name = $name;
        $this->schema = $schema;
        $this->examples = $examples ?? new Examples();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }

    public function getExamples(): Examples
    {
        return $this->examples;
    }
}
