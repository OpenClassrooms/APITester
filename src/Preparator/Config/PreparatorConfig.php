<?php

declare(strict_types=1);

namespace APITester\Preparator\Config;

class PreparatorConfig
{
    /**
     * @var string[]
     */
    public array $excludedFields = [];

    public ResponseConfig $response;

    public function __construct()
    {
        $this->response = new ResponseConfig();
    }
}
