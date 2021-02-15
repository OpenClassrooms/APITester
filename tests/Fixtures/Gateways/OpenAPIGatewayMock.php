<?php

namespace OpenAPITesting\Tests\Fixtures\Gateways;

use cebe\openapi\spec\OpenApi;
use OpenAPITesting\Gateways\OpenAPI\OpenAPIGateway;
use OpenAPITesting\Gateways\OpenAPI\OpenAPINotFoundException;

class OpenAPIGatewayMock implements OpenAPIGateway
{
    /** @var OpenApi[] */
    public static array $openAPIs = [];

    /**
     * @param OpenApi[] $openAPIs
     */
    public function __construct(array $openAPIs = [])
    {
        self::$openAPIs = $openAPIs;
    }

    public function find(string $title, string $version): OpenApi
    {
        foreach (self::$openAPIs as $openAPI) {
            if ($openAPI->info->title === $title && $openAPI->info->version === $version) {
                return $openAPI;
            }
        }
        throw new OpenAPINotFoundException();
    }
}