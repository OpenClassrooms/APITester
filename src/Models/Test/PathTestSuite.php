<?php

namespace OpenAPITesting\Models\Test;

class PathTestSuite
{
    /**
     * @var OperationTestSuite[]
     */
    protected array $operationTestSuites;

    protected string $pathName;

    /**
     * PathTestSuite constructor.
     *
     * @param OperationTestSuite[] $operationTestSuites
     * @param string $pathName
     */
    public function __construct(string $pathName, array $operationTestSuites = [])
    {
        $this->operationTestSuites = $operationTestSuites;
        $this->pathName = $pathName;
    }

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

    public function getPathName(): string
    {
        return $this->pathName;
    }
}