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
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

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
     * @return Collection<array-key, TestCase>
     */
    private function prepareForParameters(Operation $operation): Collection
    {
        $requiredParams = $operation->getRequiredParameters();

        $testCases = [];
        foreach ($requiredParams as $type => $params) {
            if (Parameter::TYPE_PATH === $type) { // Path parameters are mandatory to match the route
                continue;
            }
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

        foreach ($operation->getRequests()->where('required', true) as $definitionRequest) {
            if (!str_contains($definitionRequest->getMediaType(), 'json')) {
                continue;
            }

            $body = $definitionRequest->getBodyFromExamples();

            $testCases = $testCases->map(fn (TestCase $t) => new TestCase(
                $t->getName(),
                $t->getRequest()
                    ->withBody(Stream::create(Json::encode($body))),
                $t->getExpectedResponse()
            ));

            foreach ($body as $name => $value) {
                $testCases[] = $this->createTestCase(
                    "required_{$name}_body_field_missing",
                    $operation,
                    $requiredParameters,
                    $this->excludeFieldFromBody($name, $body)
                );
            }
        }

        $testCases[] = $this->prepareForEmptyBody($operation);

        return collect($testCases);
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

    private function prepareForEmptyBody(Operation $operation): TestCase
    {
        return $this->createTestCase(
            "required_body_missing_{$operation->getId()}",
            $operation,
            $operation->getRequiredParameters()
        );
    }
}
