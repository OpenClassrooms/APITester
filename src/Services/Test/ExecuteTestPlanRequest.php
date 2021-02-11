<?php

namespace OpenAPITesting\Services\Test;

use OpenAPITesting\Models\Test\TestPlan;

class ExecuteTestPlanRequest
{
    public TestPlan $testPlan;

    public function getTestPlan(): TestPlan
    {
        return $this->testPlan;
    }
}