<?php

namespace OpenAPITesting\Tests\Gateways\OpenAPI;

use cebe\openapi\Reader;
use cebe\openapi\spec\Operation;
use Generator;
use OpenAPITesting\Gateways\OpenAPI\OperationGateway;
use OpenAPITesting\Gateways\OpenAPI\OperationRepository;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OperationStubUpdatePet;
use PHPUnit\Framework\TestCase;

class OperationRepositoryTest extends TestCase
{
    private OperationGateway $operationGateway;

    public static function getOperations(): Generator
    {
        yield 'no filter' => [
            [],
            [
                'updatePet' => new OperationStubUpdatePet(),
                'addPet' => new OperationStubUpdatePet(),
                'findPetsByStatus' => new OperationStubUpdatePet(),
                'findPetsByTags' => new OperationStubUpdatePet(),
                'getPetById' => new OperationStubUpdatePet(),
                'updatePetWithForm' => new OperationStubUpdatePet(),
                'deletePet' => new OperationStubUpdatePet(),
                'uploadFile' => new OperationStubUpdatePet(),
                'getInventory' => new OperationStubUpdatePet(),
                'placeOrder' => new OperationStubUpdatePet(),
                'getOrderById' => new OperationStubUpdatePet(),
                'deleteOrder' => new OperationStubUpdatePet(),
                'createUser' => new OperationStubUpdatePet(),
                'createUsersWithListInput' => new OperationStubUpdatePet(),
                'loginUser' => new OperationStubUpdatePet(),
                'logoutUser' => new OperationStubUpdatePet(),
                'getUserByName' => new OperationStubUpdatePet(),
                'updateUser' => new OperationStubUpdatePet(),
                'deleteUser' => new OperationStubUpdatePet()
            ]
        ];

        yield 'One filter' => [
            [OperationGateway::FILTER_TAGS => ['pet']],
            [
                'updatePet' => new OperationStubUpdatePet(),
                'addPet' => new OperationStubUpdatePet(),
                'findPetsByStatus' => new OperationStubUpdatePet(),
                'findPetsByTags' => new OperationStubUpdatePet(),
                'getPetById' => new OperationStubUpdatePet(),
                'updatePetWithForm' => new OperationStubUpdatePet(),
                'deletePet' => new OperationStubUpdatePet(),
                'uploadFile' => new OperationStubUpdatePet()
            ]
        ];

        yield 'two filter' => [
            [OperationGateway::FILTER_TAGS => ['pet', 'store']],
            [
                'updatePet' => new OperationStubUpdatePet(),
                'addPet' => new OperationStubUpdatePet(),
                'findPetsByStatus' => new OperationStubUpdatePet(),
                'findPetsByTags' => new OperationStubUpdatePet(),
                'getPetById' => new OperationStubUpdatePet(),
                'updatePetWithForm' => new OperationStubUpdatePet(),
                'deletePet' => new OperationStubUpdatePet(),
                'uploadFile' => new OperationStubUpdatePet(),
                'getInventory' => new OperationStubUpdatePet(),
                'placeOrder' => new OperationStubUpdatePet(),
                'getOrderById' => new OperationStubUpdatePet(),
                'deleteOrder' => new OperationStubUpdatePet(),
            ]
        ];

        yield 'non existing operation id filter' => [
            [OperationGateway::FILTER_OPERATION_IDS => ['non-existing']],
            []
        ];

        yield 'one operation id filter' => [
            [OperationGateway::FILTER_OPERATION_IDS => ['placeOrder']],
            [
                'placeOrder' => new OperationStubUpdatePet(),
            ]
        ];

        yield 'two operation ids filter' => [
            [OperationGateway::FILTER_OPERATION_IDS => ['updatePet', 'placeOrder']],
            [
                'updatePet' => new OperationStubUpdatePet(),
                'placeOrder' => new OperationStubUpdatePet(),
            ]
        ];

        yield 'two operation ids + tag filter' => [
            [OperationGateway::FILTER_OPERATION_IDS => ['updatePet', 'placeOrder'],
                OperationGateway::FILTER_TAGS => ['pet']
            ],
            [
                'updatePet' => new OperationStubUpdatePet(),
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getOperations
     * @param string[] $inputFilters
     * @param Operation[] $expectedOperations
     */
    public function findAll_ReturnOperations(array $inputFilters, array $expectedOperations)
    {
        $actualOperations = $this->operationGateway->findAll($inputFilters);
        $this->assertCount(count($expectedOperations), $actualOperations);
        foreach ($expectedOperations as $operationId => $expectedOperation) {
            $actualOperation = $actualOperations[$operationId];
            $this->assertSame($expectedOperation->operationId, $actualOperation->operationId);
            $this->assertEquals($expectedOperation->tags, $actualOperation->tags);
        }
    }

    protected function setUp(): void
    {
        $openAPI = Reader::readFromJsonFile(FixturesLocation::OPEN_API_PETSTORE_FILE);
        $this->operationGateway = new OperationRepository($openAPI);
    }
}
