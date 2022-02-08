<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\Request;
use OpenAPITesting\Test\TestCase;

final class Error400TestCasesPreparator extends FieldLevelTestCasePreparator
{
    protected function getStatusCode(): int
    {
        return 400;
    }

    /**
     * @return TestCase[]
     */
    protected function prepareForParameters(array $definitionParams, Operation $operation): array
    {
        $testCases = [];
        foreach ($definitionParams as $in => $params) {
            if (Parameter::TYPE_PATH === $in) { // Path parameters are mandatory to match the route
                continue;
            }

            foreach ($params as $param) {
                $testCases[] = $this->createTestCase(
                    "required_{$param->getName()}_param_missing_{$operation->getId()}",
                    $param->getParent(),
                    $this->excludeParameter(
                        $definitionParams,
                        $in,
                        $param
                    ),
                );
            }
        }

        return $this->addRequestBody($testCases, $operation);
    }

    /**
     * @inheritDoc
     */
    protected function prepareForBodies(array $requiredParams, Operation $operation): array
    {
        $testCases = [];

        $requestBodies = $operation->getRequests();

        foreach ($requestBodies as $definitionRequest) {
            $testCases[] = $this->prepareForBodyFields($definitionRequest, $requiredParams, $operation);
        }

        $testCases = array_merge(...$testCases);

        if ($requestBodies->count() > 0) {
            $testCases[] = $this->prepareForEmptyBody($operation);
        }

        return $testCases;
    }

    protected function prepareForBodyFields(
        Request $definitionRequest,
        array $parameters,
        Operation $operation
    ): array {
        $testCases = [];

        $body = $definitionRequest->getBodyFromExamples();
        foreach ($body as $name => $value) {
            $testCases[] = $this->createTestCase(
                "required_{$name}_body_field_missing",
                $operation,
                $parameters,
                $this->excludeFieldFromBody($name, $body)
            );
        }

        return $testCases;
    }

    /**
     * @param array<string, Parameters> $parameters
     *
     * @return array<string, Parameters>
     */
    private function excludeParameter(array $parameters, string $type, Parameter $toExclude): array
    {
        $parameters[$type] = $parameters[$type]->where('name', '!=', $toExclude->getName());

        return $parameters;
    }

    private function prepareForEmptyBody(Operation $operation): TestCase
    {
        return $this->createTestCase(
            "required_body_missing_{$operation->getId()}",
            $operation,
            $operation->getRequiredParameters()
        );
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function excludeFieldFromBody(string $name, array $body): array
    {
        unset($body[$name]);

        return $body;
    }
}
