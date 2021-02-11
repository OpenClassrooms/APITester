<?php

namespace OpenAPITesting\Tests\Fixtures\Models\Test;

use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Models\Test\TestPlan;

class TestPlanStub1 extends TestPlan
{
    public function __construct()
    {
        parent::__construct(new OpenApi());
    }
}