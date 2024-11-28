<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

use APITester\Config\Filters;

class PreparatorConfig
{
    /**
     * @var string[]
     */
    public array $excludedFields = [];

    public bool $schemaValidation = true;

    public ResponseConfig $response;

    public Filters $filters;

    public function __construct()
    {
        $this->response = new ResponseConfig();
        $this->filters = new Filters();
    }
}
