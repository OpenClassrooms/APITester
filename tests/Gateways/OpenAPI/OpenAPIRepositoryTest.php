<?php

namespace OpenAPITesting\Tests\Gateways\OpenAPI;

use OpenAPITesting\Gateways\OpenAPI\InvalidFormatException;
use OpenAPITesting\Gateways\OpenAPI\NonExistingFileException;
use OpenAPITesting\Gateways\OpenAPI\OpenAPIGateway;
use OpenAPITesting\Gateways\OpenAPI\OpenAPINotFoundException;
use OpenAPITesting\Gateways\OpenAPI\OpenAPIRepository;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;

class OpenAPIRepositoryTest extends TestCase
{
    private OpenAPIGateway $openAPIFileGateway;

    /**
     * @test
     */
    public function NonExistingFile_ThrowException()
    {
        $this->openAPIFileGateway = new OpenAPIRepository(["non-existing-location"]);
        $this->expectException(NonExistingFileException::class);
        $this->openAPIFileGateway->find('test');
    }

    /**
     * @test
     */
    public function InvalidJson_ThrowException()
    {
        $this->openAPIFileGateway = new OpenAPIRepository([FixturesLocation::OPEN_API_INVALID_FORMAT_FILE]);
        $this->expectException(InvalidFormatException::class);
        $this->openAPIFileGateway->find('test');
    }

    /**
     * @test
     */
    public function NotOpenAPIFormat_ThrowException()
    {
        $this->openAPIFileGateway = new OpenAPIRepository([FixturesLocation::OPEN_API_INVALID_OPEN_API_FORMAT_FILE]);
        $this->expectException(InvalidFormatException::class);
        $this->openAPIFileGateway->find('test');
    }

    /**
     * @test
     */
    public function nonExistingTitle_ThrowException()
    {
        $this->expectException(OpenAPINotFoundException::class);
        $this->openAPIFileGateway->find('non-existing');
    }

    /**
     * @test
     */
    public function nonExistingVersion_ThrowException()
    {
        $this->expectException(OpenAPINotFoundException::class);
        $this->openAPIFileGateway->find('Swagger Petstore', '-1');
    }

    /**
     * @test
     */
    public function OpenAPITitle_ReturnOpenAPI()
    {
        $openAPI = $this->openAPIFileGateway->find('Swagger Petstore');
        $this->assertEquals('Swagger Petstore', $openAPI->info->title);
    }

    /**
     * @test
     */
    public function OpenAPITitleAndVersion_ReturnOpenAPI()
    {
        $openAPI = $this->openAPIFileGateway->find('Swagger Petstore', '1.0.0');
        $this->assertEquals('Swagger Petstore', $openAPI->info->title);
    }

    protected function setUp(): void
    {
        $this->openAPIFileGateway = new OpenAPIRepository([FixturesLocation::OPEN_API_PETSTORE_EXPANDED_FILE, FixturesLocation::OPEN_API_PETSTORE_FILE]);
    }
}
