<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Collection\Requests;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Collection;

final class Error400TestCasesPreparator extends TestCasesPreparator
{
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
     * @param array<string, Parameters> $requiredParams
     * @param array<string, mixed>      $body
     *
     * @return TestCase[]
     */
    private function prepareForParameters(Operation $operation): Collection
    {
        $requiredParams = $operation->getRequiredParameters();

        $testCases = [];
        foreach ($requiredParams as $type => $params) {
            foreach ($params as $param) {
                $testCases[] = $this->createTestCase(
                    "required_{$param->getName()}_param_missing_{$operation->getId()}",
                    $param->getParent(),
                    $this->excludeParameter(
                        $requiredParams,
                        $type,
                        $param
                    ),
                );
            }
        }

        return collect($testCases);
    }

    private function prepareForBody($testCases, Operation $operation)
    {
        $testCases = $testCases->map(
            fn (TestCase $testCase) => $this->duplicateTestCaseForBodies($testCase, $operation->getRequests())
        )->flatten();

        $testCases->add($this->prepareForEmptyBody($operation));

        return $testCases;
    }

    /**
     * @param array<string, Parameters> $parameters
     */
    private function createTestCase(string $name, Operation $operation, array $parameters): TestCase
    {
        return new TestCase(
            $name,
            new Request(
                $operation->getMethod(),
                $operation->getExamplePath(
                    null,
                    $parameters[Parameter::TYPE_QUERY]
                ),
                $parameters[Parameter::TYPE_HEADER]->toExampleArray(),
            ),
            new Response(400)
        );
    }

    /**
     * @param array<string, Parameters> $parameterExamples
     *
     * @return array<string, Parameters>
     */
    private function excludeParameter(array $parameterExamples, string $type, Parameter $toExclude): array
    {
        $parameterExamples[$type] = $parameterExamples[$type]->where('name', '!=', $toExclude->getName());

        return $parameterExamples;
    }

    /**
     * @return TestCase[]
     */
    private function duplicateTestCaseForBodies(TestCase $testCase, Requests $requests): array
    {
        $testCases = [];
        foreach ($requests as $definitionRequest) {
            $body = $definitionRequest->getBodyFromExamples();
            foreach ($body as $name => $value) {
                $request = $testCase->getRequest();
                $request = $request->withBody($this->excludeFieldFromBody($name, $body));
                $testCases[] = $testCase->withRequest($request);
            }
        }

        if ($testCases === []) {
            return [$testCase];
        }

        return $testCases;
    }

    private function prepareForEmptyBody(Operation $operation): TestCase
    {
        return new TestCase(
            "required_body_missing_{$operation->getId()}",
            new Request(
                $operation->getMethod(),
                $operation->getExamplePath(),
                $operation->getHeaders()->where('required', true)->toExampleArray(),
            ),
            new Response(400)
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
