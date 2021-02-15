<?php

namespace OpenAPITesting\Services\Test;

class PrepareTestPlanRequest
{
    public array $filters;

    public string $openAPITitle;

    public string $version;

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getOpenAPITitle(): string
    {
        return $this->openAPITitle;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}