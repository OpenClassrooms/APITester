<?php

namespace OpenAPITesting\Tests\Fixtures\Gateways;

use OpenAPITesting\Gateways\Fixture\OperationTestSuiteFixtureGateway;
use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;

class OperationTestSuiteFixtureGatewayMock implements OperationTestSuiteFixtureGateway
{
    /**
     * @var OperationTestSuiteFixture[]
     */
    public static array $operationTestSuiteFixtures = [];

    /**
     * @param OperationTestSuiteFixture[]
     */
    public function __construct(array $operationTestSuiteFixtures = [])
    {
        self::$operationTestSuiteFixtures = $operationTestSuiteFixtures;
    }

    public function findAll(array $filters = []): array
    {
        return self::$operationTestSuiteFixtures;
    }
}