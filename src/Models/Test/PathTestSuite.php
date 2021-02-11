<?php

namespace OpenAPITesting\Models\Test;

use cebe\openapi\spec\PathItem;

class PathTestSuite
{
    /**
     * @var OperationTestSuite[]
     */
    protected array $operationTestSuites;

    protected PathItem $path;

    protected string $pathName;

    public function addOperationTestSuite(OperationTestSuite $operationTestSuite)
    {
        $this->operationTestSuites[] = $operationTestSuite;
    }

    /**
     * @return OperationTestSuite[]
     */
    public function getOperationTestSuites(): array
    {
        return $this->operationTestSuites;
    }
}