<?php

namespace OpenAPITesting\Tests\Fixtures\Models\OpenAPI;

use cebe\openapi\spec\Operation;

class OperationStubFindPetsByStatus extends Operation
{
    public const OPERATION_ID = 'findPetsByStatus';

    public function __construct($data = [])
    {
        $data['operationId'] = self::OPERATION_ID;
        parent::__construct(
            [
                'operation' => new Operation($data),
                'path' => '/pets/findByStatus'
            ]);
    }
}