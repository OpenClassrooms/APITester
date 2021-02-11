<?php

namespace OpenAPITesting\Tests\OpenAPITesting\Gateways\Fixture;

use Generator;
use OpenAPITesting\Gateways\Fixture\OperationTestSuiteFixtureGateway;
use OpenAPITesting\Gateways\Fixture\OperationTestSuiteFixtureRepository;
use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;
use OpenAPITesting\Tests\Fixtures\Fixture\OperationTestSuiteFixtureStub1;
use OpenAPITesting\Tests\Fixtures\Fixture\OperationTestSuiteFixtureStub2;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;

class OperationTestSuiteFixtureRepositoryTest extends TestCase
{
    private OperationTestSuiteFixtureGateway $operationTestSuiteFixtureGateway;

    public static function getOperationTestSuiteFixtures(): Generator
    {
        yield 'empty filters' => [
            [],
            [
                OperationTestSuiteFixtureStub1::OPERATION_ID => new OperationTestSuiteFixtureStub1(),
                OperationTestSuiteFixtureStub2::OPERATION_ID => new OperationTestSuiteFixtureStub2()
            ]
        ];

        yield 'filters 1 operation id' => [
            [OperationTestSuiteFixtureGateway::FILTER_OPERATION_IDS => [OperationTestSuiteFixtureStub1::OPERATION_ID]],
            [
                OperationTestSuiteFixtureStub1::OPERATION_ID => new OperationTestSuiteFixtureStub1(),
            ]
        ];

        yield 'filters 2 operation ids' => [
            [OperationTestSuiteFixtureGateway::FILTER_OPERATION_IDS => [OperationTestSuiteFixtureStub1::OPERATION_ID, OperationTestSuiteFixtureStub2::OPERATION_ID]],
            [
                OperationTestSuiteFixtureStub1::OPERATION_ID => new OperationTestSuiteFixtureStub1(),
                OperationTestSuiteFixtureStub2::OPERATION_ID => new OperationTestSuiteFixtureStub2()
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getOperationTestSuiteFixtures
     * @param OperationTestSuiteFixture[] $expectedOperationTestSuiteFixtures
     */
    public function findAll_ReturnOperationTestSuiteFixtures(array $inputFilters, array $expectedOperationTestSuiteFixtures)
    {
        $operationTestSuiteFixtures = $this->operationTestSuiteFixtureGateway->findAll($inputFilters);
        $this->assertCount(count($expectedOperationTestSuiteFixtures), $operationTestSuiteFixtures);
        /** @var  OperationTestSuiteFixture[] $operationTestSuiteFixtures */
        foreach ($operationTestSuiteFixtures as $key => $operationTestSuiteFixture) {
            $expectedOperationTestSuiteFixture = $expectedOperationTestSuiteFixtures[$key];
            $this->assertSame($expectedOperationTestSuiteFixture->getOperationId(), $operationTestSuiteFixture->getOperationId());
            $this->assertSame($expectedOperationTestSuiteFixture->getOperationId(), $operationTestSuiteFixture->getOperationId());
        }
    }

    protected function setUp(): void
    {
        $this->operationTestSuiteFixtureGateway = new OperationTestSuiteFixtureRepository(FixturesLocation::FIXTURE_OPERATION_TEST_SUITE_1);
    }
}
