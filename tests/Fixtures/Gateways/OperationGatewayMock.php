<?php

namespace OpenAPITesting\Tests\Fixtures\Gateways;

use OpenAPITesting\Gateways\OpenAPI\OperationGateway;
use OpenAPITesting\Models\OpenAPI\Operation;

class OperationGatewayMock implements OperationGateway
{
    /** @var Operation[] */
    public static array $operations = [];

    /**
     * @param Operation[] $operations
     */
    public function __construct(array $operations = [])
    {
        self::$operations = $operations;
    }

    public function findAll(array $filters = []): array
    {
        return self::$operations;
    }
}