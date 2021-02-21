<?php

namespace OpenAPITesting\Services\Test;

use OpenAPITesting\Models\Test\TestPlan;

class ExecuteTestPlanRequest
{
    public TestPlan $testPlan;

    public function __construct(TestPlan $testPlan)
    {
        $this->testPlan = $testPlan;
    }

    public static function create(TestPlan $testPlan): ExecuteTestPlanRequest
    {
        return new static($testPlan);
    }

    public function build(): ExecuteTestPlanRequest
    {
        return $this;
    }

    public function getTestPlan(): TestPlan
    {
        return $this->testPlan;
    }
}