<?php

namespace OpenAPITesting\Gateways\Fixture;

use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;

interface OperationTestSuiteFixtureGateway
{
    public const FILTER_OPERATION_IDS = 'operation-ids';

    /**
     * @return OperationTestSuiteFixture[]
     */
    public function findAll(array $filters = []): array;
}