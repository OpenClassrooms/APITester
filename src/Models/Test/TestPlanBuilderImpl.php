<?php

namespace OpenAPITesting\Models\Test;

use cebe\openapi\spec\OpenApi;

class TestPlanBuilderImpl implements TestPlanBuilder
{
    protected OpenApi $openAPI;

    /** @var PathTestSuite[] */
    protected array $pathTestSuites;

    public function create(OpenApi $openApi): TestPlanBuilder
    {
        $this->openAPI = $openApi;

        return $this;
    }

    public function withPathTestSuites(array $pathTestSuites): TestPlanBuilder
    {
        $this->pathTestSuites = $pathTestSuites;

        return $this;
    }

    public function build(): TestPlan
    {
        $testPlan = new TestPlan($this->openAPI);
        $testPlan->addPathTestSuites($this->pathTestSuites);

        return $testPlan;
    }
}