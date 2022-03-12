<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

abstract class FieldLevelTestCasePreparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        /** @var TestCase[] */
        return $operations
            ->map(fn (Operation $op) => $this->prepareTestCases($op))
            ->flatten()
        ;
    }

    /**
     * @param array<string, Parameters> $definitionParams
     *
     * @return TestCase[]
     */
    abstract protected function prepareForParameters(
        array $definitionParams,
        Operation $operation
    ): array;

    /**
     * @param array<string, Parameters> $requiredParams
     *
     * @return TestCase[]
     */
    protected function prepareForBodies(array $requiredParams, Operation $operation): array
    {
        $testCases = [];
        foreach ($operation->getRequests() as $definitionRequest) {
            $testCases[] = $this->prepareForBodyFields($definitionRequest, $requiredParams, $operation);
        }

        return array_merge(...$testCases);
    }

    /**
     * @param array<string, Parameters> $parameters
     *
     * @return TestCase[]
     */
    abstract protected function prepareForBodyFields(
        \OpenAPITesting\Definition\Request $definitionRequest,
        array $parameters,
        Operation $operation
    ): array;

    /**
     * @param TestCase[] $testCases
     *
     * @return TestCase[]
     */
    protected function addRequestBody(array $testCases, Operation $operation): array
    {
        /** @var \OpenAPITesting\Definition\Request|null $requiredBody */
        $requiredBody = $operation->getRequests()
            ->where('required', true)
            ->first()
        ;

        if (null === $requiredBody) {
            return $testCases;
        }

        return array_map(
            static function (TestCase $t) use ($requiredBody) {
                return $t->withAddedRequestBody($requiredBody);
            },
            $testCases
        );
    }

    /**
     * @param array<array-key, mixed>   $body
     * @param array<string, Parameters> $parameters
     */
    protected function createTestCase(
        string $name,
        Operation $operation,
        array $parameters,
        array $body = null
    ): TestCase {
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
            new Response($this->getStatusCode())
        );
    }

    abstract protected function getStatusCode(): int;

    /**
     * @return TestCase[]
     */
    private function prepareTestCases(Operation $operation): array
    {
        $requiredParams = $operation->getParameters(true);

        return array_merge(
            $this->prepareForParameters($requiredParams, $operation),
            $this->prepareForBodies($requiredParams, $operation)
        );
    }
}
