<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator\Config;

final class DefinitionExamples
{
    private ?string $fixturesPath;

    public function __construct(?string $fixturesPath)
    {
        $this->fixturesPath = $fixturesPath;
    }

    public function getFixturesPath(): ?string
    {
        return $this->fixturesPath;
    }

    public function setFixturesPath(?string $fixturesPath): self
    {
        $this->fixturesPath = $fixturesPath;

        return $this;
    }
}
