<?php

namespace OpenAPITesting\Models\Test;

use cebe\openapi\spec\OpenApi;

interface TestPlanBuilder
{
    public function create(OpenApi $openApi): TestPlanBuilder;

    /**
     * @param PathTestSuite[] $pathTestSuites
     */
    public function withPathTestSuites(array $pathTestSuites): TestPlanBuilder;

    public function build(): TestPlan;
}