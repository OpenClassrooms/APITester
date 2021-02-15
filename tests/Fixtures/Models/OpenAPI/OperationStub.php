<?php

namespace OpenAPITesting\Tests\Fixtures\Models\OpenAPI;

use OpenAPITesting\Models\OpenAPI\Operation;

class OperationStub extends Operation
{
    public function __construct($data = [])
    {
        $data['operationId'] = 'operationId';
        parent::__construct(
            [
                'method' => 'get',
                'operation' => new \cebe\openapi\spec\Operation($data),
                'path' => '/path'
            ]);
    }
}