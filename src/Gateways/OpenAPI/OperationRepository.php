<?php

namespace OpenAPITesting\Gateways\OpenAPI;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;

class OperationRepository implements OperationGateway
{
    private OpenAPI $openAPI;

    public function __construct(OpenApi $openAPI)
    {
        $this->openAPI = $openAPI;
    }

    public function findAll(array $filters = []): array
    {
        $operations = [];
        foreach ($this->openAPI->paths as $pathName => $path) {
            $filteredOperations = $this->applyFilters($path->getOperations(), $filters);
            foreach ($filteredOperations as $filteredOperation) {
                $operations[$filteredOperation->operationId] = new \OpenAPITesting\Models\OpenAPI\Operation(['operation' => $filteredOperation, 'path' => $pathName]);
            }
        }

        return $operations;
    }

    /**
     * @param Operation[] $pathOperations
     * @return Operation[]
     */
    private function applyFilters(array $pathOperations, array $filters): array
    {
        return array_filter($pathOperations, function (Operation $operation) use ($filters) {
            if (array_key_exists(self::FILTER_TAGS, $filters)) {
                $tags = $filters[self::FILTER_TAGS];
                foreach ($operation->tags as $tag) {
                    if (!in_array($tag, $tags)) {
                        return false;
                    }
                }
            }
            if (array_key_exists(self::FILTER_OPERATION_IDS, $filters)) {
                $operationIds = $filters[self::FILTER_OPERATION_IDS];
                if (!in_array($operation->operationId, $operationIds)) {
                    return false;
                }
            }

            return true;
        });
    }
}