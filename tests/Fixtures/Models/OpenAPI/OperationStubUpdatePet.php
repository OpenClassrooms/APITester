<?php

namespace OpenAPITesting\Tests\Fixtures\Models\OpenAPI;

use OpenAPITesting\Models\OpenAPI\Operation;

class OperationStubUpdatePet extends Operation
{
    public const METHOD = 'PUT';

    public const OPERATION_ID = 'updatePet';

    public const PATH = '/pets';

    public function __construct($data = [])
    {
        $data['operationId'] = self::OPERATION_ID;
        parent::__construct(
            [
                'method' => self::METHOD,
                'operation' => new \cebe\openapi\spec\Operation($data),
                'path' => self::PATH
            ]);
    }
}