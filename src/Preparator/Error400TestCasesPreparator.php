<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

final class Error400TestCasesPreparator extends TestCasesPreparator
{
    public const HEADER_PARAMETER_TYPE = 'headers';
    public const QUERY_PARAMETER_TYPE = 'queryParameters';
    public const PATH_PARAMETER_TYPE = 'pathParameters';

    public static function getName(): string
    {
        return '400';
    }

    /**
     * @inheritDoc
     */
    public function prepare(Api $api): iterable
    {
        $testCases = [];
        foreach ($api->getOperations() as $operation) {
            $testCases[] = $this->prepareTestCases($operation);
        }

        return array_merge(...$testCases);
    }

    /**
     * @return TestCase[]
     */
    private function prepareTestCases(Operation $operation): array
    {
        $requiredParams = $this->getRequiredParameters($operation);

        if (0 === $operation->getRequests()->count()) {
            return $this->prepareForParameters($requiredParams);
        }

        $testCases = [];
        foreach ($operation->getRequests() as $request) {
            $body = $request->getBodyFromExamples(true);

            $testCases[] = $this->prepareForBody($requiredParams, $body, $operation);
            $testCases[] = $this->prepareForParameters($requiredParams, $body);
        }

        return array_merge(...$testCases);
    }

    /**
     * @return array<string, Parameters> $requiredParams
     */
    private function getRequiredParameters(Operation $operation): array
    {
        return [
            self::PATH_PARAMETER_TYPE => $operation->getPathParameters()->where('required', true),
            self::QUERY_PARAMETER_TYPE => $operation->getQueryParameters()->where('required', true),
            self::HEADER_PARAMETER_TYPE => $operation->getHeaders()->where('required', true),
        ];
    }

    /**
     * @param array<string, Parameters> $requiredParams
     * @param array<string, mixed>      $body
     *
     * @return TestCase[]
     */
    private function prepareForParameters(
        array $requiredParams,
        array $body = []
    ): array {
        $testCases = [];
        foreach ($requiredParams as $type => $params) {
            if (self::PATH_PARAMETER_TYPE === $type) { // Path parameters are mandatory to match the route
                continue;
            }
            foreach ($params as $param) {
                $parameters = $this->excludeParameter($requiredParams, $type, $param);

                $testCases[] = $this->createForMissingParameter(
                    $param->getParent(),
                    $param->getName(),
                    $parameters,
                    $body
                );
            }
        }

        return $testCases;
    }

    /**
     * @param array<string,Parameters> $requiredParams
     * @param array<string, mixed>     $body
     *
     * @return TestCase[]
     */
    private function prepareForBody(
        array $requiredParams,
        array $body,
        Operation $operation
    ): array {
        $testCases = [];
        foreach ($body as $name => $value) {
            $testCases[] = $this->createForMissingParameter(
                $operation,
                $name,
                $requiredParams,
                $this->excludeFieldFromBody($name, $body)
            );
        }

        return $testCases;
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
     * @param array<string, Parameters> $parameters
     * @param array<string, mixed>      $body
     */
    private function createForMissingParameter(
        Operation $operation,
        string $missing,
        array $parameters,
        array $body = null
    ): TestCase {
        return new TestCase(
            "required_{$missing}_param_missing_{$operation->getId()}",
            new Request(
                $operation->getMethod(),
                $operation->getExamplePath(
                    $parameters[self::PATH_PARAMETER_TYPE],
                    $parameters[self::QUERY_PARAMETER_TYPE]
                ),
                $parameters[self::HEADER_PARAMETER_TYPE]->toExampleArray(),
                (null !== $body && [] !== $body) ? Json::encode($body) : null
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
