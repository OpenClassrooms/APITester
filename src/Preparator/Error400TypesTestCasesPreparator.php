<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Illuminate\Support\Collection;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

final class Error400TypesTestCasesPreparator extends TestCasesPreparator
{
    public const TYPE_STRING = 'string';
    public const TYPE_NUMBER = 'number';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_ARRAY = 'array';
    public const TYPE_OBJECT = 'object';

    public const PARAMETER_ANY_TYPES = [
        self::TYPE_STRING,
        self::TYPE_NUMBER,
        self::TYPE_INTEGER,
        self::TYPE_BOOLEAN,
        self::TYPE_ARRAY,
    ];

    public const SCHEMA_ANY_TYPES = [
        self::TYPE_STRING,
        self::TYPE_NUMBER,
        self::TYPE_INTEGER,
        self::TYPE_BOOLEAN,
        self::TYPE_ARRAY,
        self::TYPE_OBJECT,
    ];

    /**
     * @inheritDoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        return $operations
            ->map(fn (Operation $op) => $this->prepareTestCases($op))
            ->flatten()
        ;
    }

    /**
     * @return TestCase[]
     */
    private function prepareTestCases(Operation $operation): iterable
    {
        $testCases = $this->prepareForParameters($operation);

        return $this->prepareForBody($testCases, $operation);
    }

    /**
     * @return Collection<array-key, TestCase>
     */
    private function prepareForParameters(Operation $operation): Collection
    {
        $requiredParams = $operation->getRequiredParameters();

        $testCases = [];
        foreach ($requiredParams as $in => $params) {
            foreach ($params as $key => $param) {
                if (self::TYPE_STRING === $param->getType()) {
                    continue;
                }
                foreach (self::PARAMETER_ANY_TYPES as $type) {
                    if ($type === $param->getType()) {
                        continue;
                    }
                    $parameters = $requiredParams;
                    $parameters[$in][$key] = $this->changeParameterType($param, $type);
                    $testCases[] = $this->createTestCase(
                        "{$param->getName()}_param_type_{$type}_{$operation->getId()}",
                        $param->getParent(),
                        $parameters
                    );
                }
            }
        }

        return collect($testCases);
    }

    /**
     * @param Collection<array-key, TestCase> $testCases
     *
     * @return Collection<array-key, TestCase>
     */
    private function prepareForBody(Collection $testCases, Operation $operation): Collection
    {
        if (!$operation->needsRequestBody()) {
            return $testCases;
        }

        $requiredParameters = $operation->getRequiredParameters();

        /** @var \OpenAPITesting\Definition\Request $definitionRequest */
        foreach ($operation->getRequests()->where('required', true) as $definitionRequest) {
            if (!str_contains($definitionRequest->getMediaType(), 'json')) {
                continue;
            }

            $bodyExamples = $definitionRequest->getBodyFromExamples();

            $testCases = $testCases->map(fn (TestCase $t) => new TestCase(
                $t->getName(),
                $t->getRequest()
                    ->withBody(Stream::create(Json::encode($bodyExamples))),
                $t->getExpectedResponse()
            ));

            foreach ($definitionRequest->getBody()->properties as $property => $schema) {
                foreach (self::SCHEMA_ANY_TYPES as $type) {
                    if ($type === $schema->type) {
                        continue;
                    }
                    $body = $bodyExamples;
                    $body[$property] = $this->getSchemaExample($type);

                    $testCases[] = $this->createTestCase(
                        "{$property}_body_field_type_{$type}_{$operation->getId()}",
                        $operation,
                        $requiredParameters,
                        $body
                    );
                }
            }
        }

        return collect($testCases);
    }

    private function changeParameterType(Parameter $param, string $type): Parameter
    {
        return (new Parameter($param->getName(), $param->isRequired(), $param->getSchema()))->addExample(
            new ParameterExample($param->getName(), $this->getParameterExamples($type))
        );
    }

    /**
     * @param array<array-key, mixed>   $body
     * @param array<string, Parameters> $parameters
     */
    private function createTestCase(string $name, Operation $operation, array $parameters, array $body = null): TestCase
    {
        return new TestCase(
            $name,
            new Request(
                $operation->getMethod(),
                $operation->getExamplePath(
                    $parameters[Parameter::TYPE_PATH],
                    $parameters[Parameter::TYPE_QUERY]
                ),
                $parameters[Parameter::TYPE_HEADER]->where('required', true)->toExampleArray(),
                null === $body ? null : Json::encode($body)
            ),
            new Response(400)
        );
    }

    private function getSchemaExample($type)
    {
        $examples = [
            self::TYPE_STRING => 'foo',
            self::TYPE_NUMBER => 1.234,
            self::TYPE_INTEGER => 5,
            self::TYPE_BOOLEAN => true,
            self::TYPE_ARRAY => ['foo', 'bar'],
            self::TYPE_OBJECT => [
                'foo' => 'bar',
            ],
        ];

        return $examples[$type];
    }

    private function getParameterExamples(string $type): string
    {
        $examples = [
            self::TYPE_STRING => 'foo',
            self::TYPE_NUMBER => '1.234',
            self::TYPE_INTEGER => '5',
            self::TYPE_BOOLEAN => 'true',
            self::TYPE_ARRAY => 'foo,bar',
        ];

        return $examples[$type];
    }
}
