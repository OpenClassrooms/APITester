<?php

namespace OpenAPITesting\Gateways\OpenAPI;

use OpenAPITesting\Models\OpenAPI\Operation;

interface OperationGateway
{
    public const FILTER_OPERATION_IDS = 'operation-ids';

    public const FILTER_TAGS = 'tags';

    /**
     * @return Operation[]
     */
    public function findAll(array $filters = []): array;
}