<?php

namespace OpenAPITesting\Tests\Models\Test;

use Generator;
use OpenAPITesting\Models\Fixture\OperationTestSuiteFixture;
use OpenAPITesting\Models\OpenAPI\Operation;
use OpenAPITesting\Models\Test\OperationTestSuite;
use OpenAPITesting\Models\Test\PathTestSuite;
use OpenAPITesting\Models\Test\PathTestSuiteFactory;
use OpenAPITesting\Models\Test\PathTestSuiteFactoryImpl;
use OpenAPITesting\Tests\Fixtures\Fixture\OperationTestSuiteFixtureStubFindPetsByStatus1;
use OpenAPITesting\Tests\Fixtures\Fixture\OperationTestSuiteFixtureStubFindPetsByStatus2;
use OpenAPITesting\Tests\Fixtures\Fixture\OperationTestSuiteFixtureStubUpdatePet;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OperationStubFindPetsByStatus;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OperationStubUpdatePet;
use PHPUnit\Framework\TestCase;

class PathTestSuiteFactoryImplTest extends TestCase
{
    private PathTestSuiteFactory $factory;

    public static function createPathTestSuitesProvider(): Generator
    {
        yield 'Operation empty and Test Case empty, return empty' =>
        [
            [], [], [],
        ];

        yield 'Operation empty, return empty' =>
        [
            [], [], [new OperationTestSuiteFixtureStubUpdatePet()],
        ];

        yield 'Test Case empty, return empty' =>
        [
            [], [new OperationStubFindPetsByStatus()], [],
        ];

        yield 'One path, One Operation, One Fixture' =>
        [
            [OperationStubUpdatePet::PATH => new PathTestSuite(OperationStubUpdatePet::PATH, [new OperationTestSuite(new OperationStubUpdatePet(), new OperationTestSuiteFixtureStubUpdatePet())])],
            [new OperationStubUpdatePet()],
            [new OperationTestSuiteFixtureStubUpdatePet()],
        ];

        yield 'Two paths, with One Operation each, and One Fixture, Return one path' =>
        [
            [
                OperationStubUpdatePet::PATH => new PathTestSuite(OperationStubUpdatePet::PATH, [new OperationTestSuite(new OperationStubUpdatePet(), new OperationTestSuiteFixtureStubUpdatePet())]),
            ],
            [new OperationStubUpdatePet(), new OperationStubFindPetsByStatus()],
            [new OperationTestSuiteFixtureStubUpdatePet()],
        ];

        yield 'Two paths, with One Operation, and One Fixture each, return one path' =>
        [
            [
                OperationStubUpdatePet::PATH => new PathTestSuite(OperationStubUpdatePet::PATH, [new OperationTestSuite(new OperationStubUpdatePet(), new OperationTestSuiteFixtureStubUpdatePet())]),
            ],
            [new OperationStubUpdatePet()],
            [new OperationTestSuiteFixtureStubFindPetsByStatus1(), new OperationTestSuiteFixtureStubUpdatePet()],
        ];

        yield 'Two paths, with One Operation each, and One or two Fixtures each' =>
        [
            [
                OperationStubUpdatePet::PATH => new PathTestSuite(OperationStubUpdatePet::PATH, [new OperationTestSuite(new OperationStubUpdatePet(), new OperationTestSuiteFixtureStubUpdatePet())]),
                OperationStubFindPetsByStatus::PATH => new PathTestSuite(OperationStubFindPetsByStatus::PATH, [new OperationTestSuite(new OperationStubFindPetsByStatus(), new OperationTestSuiteFixtureStubFindPetsByStatus1())])
            ],
            [new OperationStubUpdatePet(), new OperationStubFindPetsByStatus()],
            [new OperationTestSuiteFixtureStubFindPetsByStatus1(), new OperationTestSuiteFixtureStubFindPetsByStatus2(), new OperationTestSuiteFixtureStubUpdatePet()],
        ];
    }

    /**
     * @test
     * @dataProvider createPathTestSuitesProvider
     * @param PathTestSuite[] $expectedPathTestSuites
     * @param Operation[] $operations
     * @param OperationTestSuiteFixture[] $operationTestSuiteFixtures
     */
    public function create_Return(array $expectedPathTestSuites, array $operations, array $operationTestSuiteFixtures)
    {
        $actualPathTestSuites = $this->factory->createPathTestSuites($operations, $operationTestSuiteFixtures);
        $this->assertCount(count($expectedPathTestSuites), $actualPathTestSuites);
        foreach ($expectedPathTestSuites as $path => $expectedPathTestSuite) {
            $actualPathTestSuite = $actualPathTestSuites[$path];
            $this->assertEquals($expectedPathTestSuite->getPathName(), $actualPathTestSuite->getPathName());
            $expectedOperationTestSuites = $expectedPathTestSuite->getOperationTestSuites();
            $actualOperationTestSuites = $actualPathTestSuite->getOperationTestSuites();
            $this->assertCount(count($expectedOperationTestSuites), $actualOperationTestSuites);
            foreach ($expectedOperationTestSuites as $key => $operationTestSuite) {
                $this->assertCount(count($operationTestSuite->getTestCases()), $actualOperationTestSuites[$key]->getTestCases());
            }
        }
    }

    protected function setUp(): void
    {
        $this->factory = new PathTestSuiteFactoryImpl();
    }
}