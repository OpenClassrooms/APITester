<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use cebe\openapi\spec\Schema;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Definition\Request;

final class Error400BadTypesTestCasesPreparator extends FieldLevelTestCasePreparator
{
    public const STRING_TYPE = 'string';
    public const NUMBER_TYPE = 'number';
    public const INTEGER_TYPE = 'integer';
    public const BOOLEAN_TYPE = 'boolean';
    public const ARRAY_TYPE = 'array';
    public const OBJECT_TYPE = 'object';

    public const PARAMETER_TYPES = [
        self::STRING_TYPE,
        self::NUMBER_TYPE,
        self::INTEGER_TYPE,
        self::BOOLEAN_TYPE,
        self::ARRAY_TYPE,
    ];

    public const SCHEMA_TYPES = [
        self::STRING_TYPE,
        self::NUMBER_TYPE,
        self::INTEGER_TYPE,
        self::BOOLEAN_TYPE,
        self::ARRAY_TYPE,
        self::OBJECT_TYPE,
    ];

    protected function getStatusCode(): int
    {
        return 400;
    }

    /**
     * @inheritDoc
     */
    protected function prepareForParameters(array $definitionParams, Operation $operation): array
    {
        $testCases = [];
        foreach ($definitionParams as $in => $params) {
            foreach ($params as $key => $param) {
                if (self::STRING_TYPE === $param->getType()) {
                    continue;
                }
                foreach (self::PARAMETER_TYPES as $type) {
                    if ($type === $param->getType()) {
                        continue;
                    }
                    $parameters = $definitionParams;
                    $parameters[$in][$key] = $this->changeParameterType($param, $type);
                    $testCases[] = $this->createTestCase(
                        "{$param->getName()}_param_type_{$type}_{$operation->getId()}",
                        $param->getParent(),
                        $parameters
                    );
                }
            }
        }

        return $this->addRequestBody($testCases, $operation);
    }

    /**
     * @inheritDoc
     */
    protected function prepareForBodyFields(
        Request $definitionRequest,
        array $parameters,
        Operation $operation
    ): array {
        $testCases = [];
        /** @var Schema $schema */
        foreach ($definitionRequest->getBody()->properties as $property => $schema) {
            foreach (self::SCHEMA_TYPES as $type) {
                if ($type === $schema->type) {
                    continue;
                }
                $body = $definitionRequest->getBodyFromExamples();
                $body[$property] = $this->getSchemaExample($type);

                $testCases[] = $this->createTestCase(
                    "{$property}_body_field_type_{$type}_{$operation->getId()}",
                    $operation,
                    $parameters,
                    $body
                );
            }
        }

        return $testCases;
    }

    private function changeParameterType(Parameter $param, string $type): Parameter
    {
        return (new Parameter($param->getName(), $param->isRequired(), $param->getSchema()))->addExample(
            new ParameterExample($param->getName(), $this->getParameterExamples($type))
        );
    }

    private function getParameterExamples(string $type): string
    {
        $examples = [
            self::STRING_TYPE => 'foo',
            self::NUMBER_TYPE => '1.234',
            self::INTEGER_TYPE => '5',
            self::BOOLEAN_TYPE => 'true',
            self::ARRAY_TYPE => 'foo,bar',
        ];

        return $examples[$type];
    }

    /**
     * @return mixed
     */
    private function getSchemaExample(string $type)
    {
        $examples = [
            self::STRING_TYPE => 'foo',
            self::NUMBER_TYPE => 1.234,
            self::INTEGER_TYPE => 5,
            self::BOOLEAN_TYPE => true,
            self::ARRAY_TYPE => ['foo', 'bar'],
            self::OBJECT_TYPE => [
                'foo' => 'bar',
            ],
        ];

        return $examples[$type];
    }
}
