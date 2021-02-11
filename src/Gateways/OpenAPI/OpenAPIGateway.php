<?php

namespace OpenAPITesting\Gateways\OpenAPI;

use cebe\openapi\spec\OpenApi;

interface OpenAPIGateway
{
    public function find(string $title, string $version): OpenApi;
}