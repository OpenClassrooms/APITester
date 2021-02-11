<?php

namespace OpenAPITesting\Tests\Fixtures\Models\Fixture;

use OpenAPITesting\Models\Fixture\OperationTestCaseFixture;

class OperationTestCaseFixtureStub2 extends OperationTestCaseFixture
{
    protected string $description = "Scenario 2 description";

    protected string $id = "Scenario id 1";

    protected array $request = [
        'parameters' => [
            'path' => ['id' => 1],
            'query' => ['category' => 'category test']],
        'header' => ['accept' => 'application/json'],
        'body' => 'Request Body'
    ];

    protected array $responses = [
        'statusCode' => 200,
        'header' => ['accept' => 'application/json'],
        'body' => 'Response Body'
    ];

    protected string $title = 'Scenario 1';
}