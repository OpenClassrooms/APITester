<?php

namespace OpenAPITesting\Services\Test;

class PrepareTestPlanRequest
{
    public array $filters;

    public string $openAPITitle;

    public string $version;

    public static function create(): PrepareTestPlanRequest
    {
        return new static();
    }

    public function withFilters(array $filters): PrepareTestPlanRequest
    {
        $this->filters = $filters;

        return $this;
    }

    public function openAPITitle(string $openAPITitle): PrepareTestPlanRequest
    {
        $this->openAPITitle = $openAPITitle;

        return $this;
    }

    public function version(string $version): PrepareTestPlanRequest
    {
        $this->version = $version;

        return $this;
    }

    public function build(): PrepareTestPlanRequest
    {
        return $this;
    }

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