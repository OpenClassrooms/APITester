<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\ParameterExample;
use APITester\Definition\Request;
use cebe\openapi\spec\Schema;

final class Error400BadFormatTestCasesPreparator extends FieldLevelTestCasePreparator
{
    public const STRING_TYPE = 'string';
    public const NUMBER_TYPE = 'number';
    public const INTEGER_TYPE = 'integer';

    public const FLOAT_FORMAT = 'float';
    public const DOUBLE_FORMAT = 'double';
    public const INT32_FORMAT = 'int32';
    public const INT64_FORMAT = 'int64';
    public const UUID_FORMAT = 'uuid';
    public const DATE_FORMAT = 'date';
    public const DATETIME_FORMAT = 'date-time';
    public const DATE_INTERVAL_FORMAT = 'date-interval';
    public const EMAIL_FORMAT = 'email';
    public const BYTE_FORMAT = 'byte';

    public const BAD_FORMAT_EXAMPLES = [
        self::STRING_TYPE => [
            self::UUID_FORMAT => 'foo',
            self::DATE_FORMAT => 'foo',
            self::DATETIME_FORMAT => 'foo',
            self::DATE_INTERVAL_FORMAT => 'foo',
            self::EMAIL_FORMAT => 'foo',
            self::BYTE_FORMAT => 'foo',
        ],
        self::NUMBER_TYPE => [
            // number type includes integer type, and they do not match float and double format
            self::FLOAT_FORMAT => 123,
            self::DOUBLE_FORMAT => 123,
        ],
        self::INTEGER_TYPE => [
            // int over 32 bits
            self::INT32_FORMAT => 50000 * 1000000000,
            // int over 64 bits
            self::INT64_FORMAT => 50000000000000 * 1000000000,
        ],
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
                if (!$this->isParameterSupported($param)) {
                    continue;
                }

                /** @var string $type */
                $type = $param->getType();
                /** @var string $format */
                $format = $param->getFormat();

                $parameters = $definitionParams;
                $parameters[$in][$key] = $this->changeParameterFormat($param, $type, $format);
                $testCases[] = $this->createTestCase(
                    "{$param->getName()}_param_bad_format_{$operation->getId()}",
                    $param->getParent(),
                    $parameters
                );
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
            if (!$this->isSchemaSupported($schema)) {
                continue;
            }
            $body = $definitionRequest->getBodyFromExamples();
            $body[$property] = $this->getExample($schema->type, $schema->format);

            $testCases[] = $this->createTestCase(
                "{$property}_body_field_bad_format_{$operation->getId()}",
                $operation,
                $parameters,
                $body
            );
        }

        return $testCases;
    }

    private function isParameterSupported(Parameter $parameter): bool
    {
        $type = $parameter->getType();
        $format = $parameter->getFormat();

        return
            null !== $type
            && null !== $format
            && \in_array($type, $this->getSupportedTypes(), true)
            && \in_array($format, $this->getSupportedFormats($type), true);
    }

    private function changeParameterFormat(Parameter $param, string $type, string $format): Parameter
    {
        return (new Parameter($param->getName(), $param->isRequired(), $param->getSchema()))->addExample(
            new ParameterExample($param->getName(), (string) $this->getExample($type, $format))
        );
    }

    /**
     * @return string[]
     */
    private function getSupportedTypes(): array
    {
        return array_keys(self::BAD_FORMAT_EXAMPLES);
    }

    /**
     * @return string[]
     */
    private function getSupportedFormats(string $type): array
    {
        return array_keys(self::BAD_FORMAT_EXAMPLES[$type]);
    }

    /**
     * @return mixed|null
     */
    private function getExample(string $type, string $format)
    {
        return self::BAD_FORMAT_EXAMPLES[$type][$format] ?? null;
    }

    private function isSchemaSupported(Schema $schema): bool
    {
        return
            null !== $schema->type
            && null !== $schema->format
            && \in_array($schema->type, $this->getSupportedTypes(), true)
            && \in_array($schema->format, $this->getSupportedFormats($schema->type), true);
    }
}
