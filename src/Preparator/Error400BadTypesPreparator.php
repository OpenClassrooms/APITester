<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Body;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Operation;
use APITester\Preparator\Foundation\Error400Preparator;
use cebe\openapi\spec\Schema;

final class Error400BadTypesPreparator extends Error400Preparator
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

    /**
     * @inheritDoc
     */
    protected function prepareForParameters(array $definitionParams, Operation $operation): array
    {
        $example = $operation->getExample();
        $testCases = [];
        foreach ($definitionParams as $in => $params) {
            foreach ($params as $param) {
                if (null === $param->getType() || self::STRING_TYPE === $param->getType()) {
                    continue;
                }
                foreach (self::PARAMETER_TYPES as $type) {
                    if ($type === $param->getType()) {
                        continue;
                    }

                    $testCases[] = $this->buildTestCase(
                        $example
                            ->withParameter(
                                $param->getName(),
                                $this->getTypeExample($type),
                                $in
                            )
                            ->setName("{$param->getName()}_param_bad_{$type}_type")
                            ->setStatusCode(400)
                    );
                }
            }
        }

        return $this->addRequestBody($testCases, $operation);
    }

    /**
     * @inheritDoc
     */
    protected function prepareForBodyFields(Body $body, array $parameters, Operation $operation): array
    {
        $example = $operation->getExample();
        $testCases = [];
        /** @var Schema $schema */
        foreach ($body->getSchema()->properties as $property => $schema) {
            foreach (self::SCHEMA_TYPES as $type) {
                if ($type === $schema->type) {
                    continue;
                }

                $content = $body->getExample();
                $content[$property] = $this->getSchemaExample($type);
                $testCases[] = $this->buildTestCase(
                    $example
                        ->withBody(BodyExample::create($content))
                        ->setName("{$property}_body_field_type_{$type}")
                        ->setStatusCode(400)
                );
            }
        }

        return $testCases;
    }

    private function getTypeExample(string $type): string
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
