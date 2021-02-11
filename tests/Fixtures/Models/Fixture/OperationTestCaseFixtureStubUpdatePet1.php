<?php

namespace OpenAPITesting\Tests\Fixtures\Models\Fixture;

use Nyholm\Psr7\Response;
use OpenAPITesting\Models\Fixture\OperationTestCaseFixture;

class OperationTestCaseFixtureStubUpdatePet1 extends OperationTestCaseFixture
{
    protected string $description = "Scenario 1 description";

    protected string $id = "Scenario id 1";

    protected array $request = [
        'parameters' => [
            'path' => ['id' => 1],
            'query' => ['category' => 'category test']],
        'header' => ['accept' => 'application/json'],
        'body' => 'Request Body'
    ];

    protected string $title = 'Scenario 1';

    public function __construct()
    {
        $this->responses = [
            new Response(200, ['accept' => 'application/json'], 'Response Body')
        ];
    }
}