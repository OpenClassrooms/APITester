<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\ParameterExample;
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
            if ($operation->getRequests()->count() > 0) {
                foreach ($operation->getRequests() as $request) {
                    $testCases[] = $this->prepareForRequest($request);
                }
            } else {
                $testCases[] = $this->prepareForOperation($operation);
            }
        }

        return array_merge(...$testCases);
    }

    /**
     * @return TestCase[]
     */
    private function prepareForRequest(\OpenAPITesting\Definition\Request $request): array
    {
        $testCases = [];

        $body = $request->buildBodyFromExamples(true);

        $requiredParams = $this->getRequiredParameters($request->getParent());

        $parameterExamples = [];
        foreach ($requiredParams as $type => $parameters) {
            $parameterExamples[$type] = $this->getParameterExamples($parameters);
        }

        foreach ($body as $name => $value) {
            $bodyWithMissingParam = $body;
            unset($bodyWithMissingParam[$name]);
            $testCases[] = $this->createForMissingParameter(
                $request->getParent(),
                $name,
                $parameterExamples,
                $bodyWithMissingParam
            );
        }

        return array_merge(
            $testCases,
            $this->prepareForParameters($requiredParams, $parameterExamples, $body)
        );
    }

    /**
     * @return TestCase[]
     */
    private function prepareForOperation(Operation $operation): array
    {
        $requiredParams = $this->getRequiredParameters($operation);

        $parameterExamples = [];
        foreach ($requiredParams as $type => $parameters) {
            $parameterExamples[$type] = $this->getParameterExamples($parameters);
        }

        return $this->prepareForParameters($requiredParams, $parameterExamples);
    }

    /**
     * @return array<string,Parameter[]> $requiredParams
     */
    private function getRequiredParameters(Operation $operation): array
    {
        return [
            self::PATH_PARAMETER_TYPE => array_filter(
                $operation->getPathParameters()->toArray(),
                static fn (Parameter $p) => $p->isRequired()
            ),
            self::QUERY_PARAMETER_TYPE => array_filter(
                $operation->getQueryParameters()->toArray(),
                static fn (Parameter $p) => $p->isRequired()
            ),
            self::HEADER_PARAMETER_TYPE => array_filter(
                $operation->getHeaders()->toArray(),
                static fn (Parameter $p) => $p->isRequired()
            ),
        ];
    }

    /**
     * @param Parameter[] $parameters
     *
     * @return ParameterExample[]
     */
    private function getParameterExamples(array $parameters): array
    {
        return array_map(static fn (Parameter $p): ParameterExample => $p->getExamples()->toArray()[0], $parameters);
    }

    /**
     * @param array<string,ParameterExample[]> $parameters
     * @param array<string, mixed> $body
     */
    private function createForMissingParameter(
        Operation $operation,
        string $missing,
        array $parameters,
        array $body = null
    ): TestCase {
        $formattedHeaders = [];
        foreach ($parameters[self::HEADER_PARAMETER_TYPE] as $header) {
            $formattedHeaders[$header->getName()] = $header->getValue();
        }

        return new TestCase(
            "required_{$missing}_param_missing_{$operation->getId()}",
            new Request(
                $operation->getMethod(),
                $operation->getPathFromExamples(
                    $parameters[self::PATH_PARAMETER_TYPE],
                    $parameters[self::QUERY_PARAMETER_TYPE]
                ),
                $formattedHeaders,
                (null !== $body && [] !== $body) ? Json::encode($body) : null
            ),
            new Response(400)
        );
    }

    /**
     * @param array<string,Parameter[]> $requiredParams
     * @param array<string,ParameterExample[]> $parameterExamples
     * @param array<string, string|array<mixed>> $body
     *
     * @return TestCase[]
     */
    private function prepareForParameters(
        array $requiredParams,
        array $parameterExamples,
        array $body = []
    ): array {
        $testCases = [];
        foreach ([self::HEADER_PARAMETER_TYPE, self::QUERY_PARAMETER_TYPE] as $type) {
            foreach ($requiredParams[$type] as $param) {
                $parameterExamplesCopy = $parameterExamples;
                $parameterExamplesCopy[$type] = array_filter(
                    $parameterExamplesCopy[$type],
                    static function (ParameterExample $p) use ($param) {
                        return $p->getName() !== $param->getName();
                    }
                );

                $testCases[] = $this->createForMissingParameter(
                    $param->getParent(),
                    $param->getName(),
                    $parameterExamplesCopy,
                    $body
                );
            }
        }

        return $testCases;
    }
}
