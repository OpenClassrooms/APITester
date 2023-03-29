<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Body;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use cebe\openapi\spec\Schema;

final class Error400BadFormatsPreparator extends Error400Preparator
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
            self::INT32_FORMAT => 50000 * 1_000_000_000,
            // int over 64 bits
            self::INT64_FORMAT => 50_000_000_000_000 * 1_000_000_000,
        ],
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
                if (!$this->isParameterSupported($param)) {
                    continue;
                }

                if (null === $param->getType() || null === $param->getFormat()) {
                    continue;
                }

                $testCases[] = $this->buildTestCase(
                    $example
                        ->withParameter(
                            $param->getName(),
                            (string) $this->getFormatExample(
                                $param->getType(),
                                $param->getFormat()
                            ),
                            $in
                        )
                        ->setName("{$param->getName()}_param_bad_{$param->getFormat()}_format")
                        ->setStatusCode('400')
                );
            }
        }

        return $this->addRequestBody($testCases, $operation);
    }

    /**
     * @inheritDoc
     */
    protected function prepareForBodyFields(Body $body, array $parameters, Operation $operation): array
    {
        $testCases = [];
        /** @var Schema $schema */
        foreach ($body->getSchema()->properties as $property => $schema) {
            if (!$this->isSchemaSupported($schema)) {
                continue;
            }
            $example = $body->getExample();
            $example[$property] = $this->getFormatExample($schema->type, $schema->format);
            $testCases[] = $this->createTestCase(
                "{$property}_body_field_bad_format_{$operation->getId()}",
                $operation,
                $parameters,
                $example
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

    /**
     * @return mixed|null
     */
    private function getFormatExample(string $type, string $format)
    {
        return self::BAD_FORMAT_EXAMPLES[$type][$format] ?? null;
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

    private function isSchemaSupported(Schema $schema): bool
    {
        return
            null !== $schema->type
            && null !== $schema->format
            && \in_array($schema->type, $this->getSupportedTypes(), true)
            && \in_array($schema->format, $this->getSupportedFormats($schema->type), true);
    }
}
