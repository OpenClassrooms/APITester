<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Parameters;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Test\TestCase;
use APITester\Util\Json;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

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
        \APITester\Definition\Request $definitionRequest,
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
        /** @var \APITester\Definition\Request|null $requiredBody */
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
        $request = new Request(
            $operation->getMethod(),
            $operation->getExamplePath(
                null,
                $parameters[Parameter::TYPE_QUERY]
            ),
            $parameters[Parameter::TYPE_HEADER]->where('required', true)->toExampleArray(),
            null === $body ? null : Json::encode($body)
        );

        return $this->buildTestCase(
            $operation,
            $request,
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
