<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Body;
use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Parameters;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Test\TestCase;

abstract class Error400Preparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var iterable<array-key, TestCase> */
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
    abstract protected function prepareForParameters(array $definitionParams, Operation $operation): array;

    /**
     * @param array<string, Parameters> $requiredParams
     *
     * @return TestCase[]
     */
    protected function prepareForBodies(array $requiredParams, Operation $operation): array
    {
        $testCases = [];
        foreach ($operation->getRequestBodies() as $definitionRequest) {
            $testCases[] = $this->prepareForBodyFields($definitionRequest, $requiredParams, $operation);
        }

        return array_merge(...$testCases);
    }

    /**
     * @param array<string, Parameters> $parameters
     *
     * @return TestCase[]
     */
    abstract protected function prepareForBodyFields(Body $body, array $parameters, Operation $operation): array;

    /**
     * @param TestCase[] $testCases
     *
     * @return TestCase[]
     */
    protected function addRequestBody(array $testCases, Operation $operation): array
    {
        /** @var Body|null $requiredBody */
        $requiredBody = $operation->getRequestBodies()
            ->where('required', true)
            ->first()
        ;

        if ($requiredBody === null) {
            return $testCases;
        }

        return array_map(
            static fn (TestCase $t) => $t->withRequestBody($requiredBody),
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
        array $body = []
    ): TestCase {
        return $this->buildTestCase(
            OperationExample::create($name, $operation)
                ->setAutoComplete(false)
                ->setQueryParameters($parameters[Parameter::TYPE_QUERY]->getExamples())
                ->setPathParameters($parameters[Parameter::TYPE_PATH]->getExamples())
                ->setHeaders(
                    $parameters[Parameter::TYPE_HEADER]
                        ->where('required', true)
                        ->getExamples()
                )
                ->setBody(BodyExample::create($body))
                ->setResponse(
                    ResponseExample::create()
                        ->setStatusCode($this->getStatusCode())
                ),
        );
    }

    protected function getStatusCode(): string
    {
        return '400';
    }

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
