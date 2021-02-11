<?php

namespace OpenAPITesting\Gateways\OpenAPI;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;

class OpenAPIRepository implements OpenAPIGateway
{
    /**
     * @var string[]
     */
    private array $fileLocations = [];

    /**
     * @var OpenApi[][]
     */
    private ?array $openAPIs = null;

    public function __construct(array $fileLocations)
    {
        $this->fileLocations = $fileLocations;
    }

    public function find(string $title, ?string $version = null): OpenApi
    {
        if ($this->openAPIs === null) {
            $this->load();
        }
        if (!array_key_exists($title, $this->openAPIs)) {
            throw new OpenAPINotFoundException('Title: ' . $title);
        }

        if ($version !== null) {
            if (!array_key_exists($version, $this->openAPIs[$title])) {
                throw new OpenAPINotFoundException('Title: ' . $title . ' - Version: ' . $version);
            }

            return $this->openAPIs[$title][$version];
        }

        return $this->openAPIs[$title][max(array_keys($this->openAPIs[$title]))];
    }

    private function load()
    {
        foreach ($this->fileLocations as $fileLocation) {
            $content = $this->getContent($fileLocation);
            $this->checkJson($content);
            $openAPI = Reader::readFromJson($content);
            if (false === $openAPI->validate()) {
                throw new InvalidFormatException(implode("\n", $openAPI->getErrors()));
            }
            $this->openAPIs[$openAPI->info->title][$openAPI->info->version] = $openAPI;
        }
    }

    private function getContent(string $fileLocation): string
    {
        $content = @file_get_contents($fileLocation);
        if ($content === false) {
            throw new NonExistingFileException($fileLocation);
        }

        return $content;
    }

    private function checkJson(string $content): void
    {
        @json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidFormatException();
        }
    }
}