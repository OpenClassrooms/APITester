<?php

namespace OpenAPITesting\Services\Test;

class PrepareTestPlanRequest
{
    protected array $filters;

    public function getFilters(): array
    {
        return $this->filters;
    }
}