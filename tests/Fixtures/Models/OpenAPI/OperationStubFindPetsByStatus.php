<?php

namespace OpenAPITesting\Tests\Fixtures\Models\OpenAPI;

use cebe\openapi\spec\Operation;

class OperationStubFindPetsByStatus extends \OpenAPITesting\Models\OpenAPI\Operation
{
    public const METHOD = 'get';

    public const OPERATION_ID = 'findPetsByStatus';

    public const PATH = '/pets/findByStatus';

    public function __construct($data = [])
    {
        $data['operationId'] = self::OPERATION_ID;
        parent::__construct(
            [
                'method' => self::METHOD,
                'operation' => new Operation($data),
                'path' => self::PATH
            ]);
    }
}