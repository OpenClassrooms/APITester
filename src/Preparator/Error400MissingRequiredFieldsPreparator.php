<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Body;
use APITester\Definition\Collection\Parameters;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Foundation\Error400Preparator;
use APITester\Test\TestCase;

final class Error400MissingRequiredFieldsPreparator extends Error400Preparator
{
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
        $testCases = parent::prepareForBodies($requiredParams, $operation);

        if ($operation->getRequestBodies()->count() > 0) {
            $testCases[] = $this->prepareForEmptyBody($operation);
        }

        return $testCases;
    }

    /**
     * @inheritDoc
     */
    protected function prepareForBodyFields(Body $body, array $parameters, Operation $operation): array
    {
        $testCases = [];

        $body = $body->getExample();
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
            $operation->getParameters(true)
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