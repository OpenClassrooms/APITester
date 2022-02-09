<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class Preparator
{
    /**
     * @var string[]
     */
    public array $excludedFields = [];

    /**
     * @var array<array-key, mixed>
     */
    public array $responseBody = [];

    /**
     * @var array<mixed>
     */
    public array $subConfig = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        /** @var string[] $excludedFields */
        $excludedFields = $data['excludedFields'] ?? [];

        /** @var array<mixed> $responseBody */
        $responseBody = $data['responseBody'] ?? [];

        $this->responseBody = $responseBody;
        $this->excludedFields = $excludedFields;
        $this->subConfig = $data;
    }
}
