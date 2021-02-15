<?php

namespace OpenAPITesting\Models\Test;

class PathTestSuiteFactoryImpl implements PathTestSuiteFactory
{
    public function createPathTestSuites(array $operations, array $operationTestSuiteFixtures): array
    {
        $pathTestSuites = [];
        foreach ($operations as $operationId => $operation) {
            if (array_key_exists($operationId, $operationTestSuiteFixtures)) {
                if (!array_key_exists($operation->getPath(), $pathTestSuites)) {
                    $pathTestSuites[$operation->getPath()] = new PathTestSuite($operation->getPath());
                }
                $pathTestSuite = $pathTestSuites[$operation->getPath()];
                $pathTestSuite->addOperationTestSuite(new OperationTestSuite($operation, $operationTestSuiteFixtures[$operationId]));
            }
        }

        return $pathTestSuites;
    }
}