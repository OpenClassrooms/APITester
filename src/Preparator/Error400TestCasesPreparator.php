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
        $aa = [];
        foreach ($api->getOperations() as $operation) {
            if ($operation->getRequests()->count() > 0) {
                foreach ($operation->getRequests() as $request) {
                    $aa[] = $this->prepareForRequest($request);
                }
            }
            $aa[] = $this->prepareForParameters($operation);
        }

        return array_merge(...$aa);
    }

    /**
     * @return TestCase[]
     */
    private function prepareForRequest(\OpenAPITesting\Definition\Request $request): array
    {
        $testCases = [];

        $requiredExamples = [];
        foreach ($request->getBody()->required as $requiredField) {
            $requiredExamples[$requiredField] = $request->getBody(
                )->properties[$requiredField]->example ?? $request->getBody()->example[$requiredField];
        }

        foreach ($requiredExamples as $name => $value) {
            $body = $requiredExamples;
            unset($body[array_search($name, $body, true)]);
            $testCases[] = $this->prepareForParameters($request->getParent(), $body);
        }

        return array_merge(...$testCases);
    }

    /**
     * @param array<string, string|int> $body
     *
     * @return TestCase[]
     */
    private function prepareForParameters(Operation $operation, array $body = []): array
    {
        $requiredParams = $this->getRequiredParameters($operation);

        $parameterExamples = [];
        foreach ($requiredParams as $type => $parameters) {
            $parameterExamples[$type] = $this->getParameterExamples($parameters);
        }

        return array_merge(
            $this->prepareForQueryParams($requiredParams, $parameterExamples, $body),
            $this->prepareForHeaders($requiredParams, $parameterExamples, $body)
        );
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
     * @param array<string,Parameter[]> $requiredParams
     * @param array<string,ParameterExample[]> $parameterExamples
     * @param array<string, string|int> $body
     *
     * @return TestCase[]
     */
    private function prepareForQueryParams(array $requiredParams, array $parameterExamples, array $body = []): array
    {
        $testCases = [];
        foreach ($requiredParams[self::QUERY_PARAMETER_TYPE] as $parameter) {
            $queryParams = array_filter(
                $parameterExamples[self::QUERY_PARAMETER_TYPE],
                static function (ParameterExample $p) use ($parameter) {
                    return $p->getName() !== $parameter->getName();
                }
            );

            $testCases[] = $this->createForMissingParameter(
                $parameter,
                $parameterExamples[self::PATH_PARAMETER_TYPE],
                $queryParams,
                $parameterExamples[self::HEADER_PARAMETER_TYPE],
                $body
            );
        }

        return $testCases;
    }

    /**
     * @param array<string,Parameter[]> $requiredParams
     * @param array<string,ParameterExample[]> $parameterExamples
     * @param array<string, string|int> $body
     *
     * @return TestCase[]
     */
    private function prepareForHeaders(array $requiredParams, array $parameterExamples, array $body = []): array
    {
        $testCases = [];
        foreach ($requiredParams[self::HEADER_PARAMETER_TYPE] as $header) {
            $headers = array_filter(
                $parameterExamples[self::HEADER_PARAMETER_TYPE],
                static function (ParameterExample $p) use ($header) {
                    return $p->getName() !== $header->getName();
                }
            );

            $testCases[] = $this->createForMissingParameter(
                $header,
                $parameterExamples[self::PATH_PARAMETER_TYPE],
                $parameterExamples[self::QUERY_PARAMETER_TYPE],
                $headers,
                $body
            );
        }

        return $testCases;
    }

    /**
     * @param ParameterExample[] $pathParams
     * @param ParameterExample[] $queryParams
     * @param ParameterExample[] $headers
     * @param array<string, string|int> $body
     */
    private function createForMissingParameter(
        Parameter $missing,
        array $pathParams,
        array $queryParams,
        array $headers,
        array $body = []
    ): TestCase {
        $formattedHeaders = [];
        foreach ($headers as $header) {
            $formattedHeaders[$header->getName()] = $header->getValue();
        }

        return new TestCase(
            "required_{$missing->getName()}_param_missing_{$missing->getParent()->getId()}",
            new Request(
                $missing->getParent()->getMethod(),
                $missing->getParent()->getPathFromExamples(
                    $pathParams,
                    $queryParams
                ),
                $formattedHeaders,
                Json::encode($body)
            ),
            new Response(400)
        );
    }
}
