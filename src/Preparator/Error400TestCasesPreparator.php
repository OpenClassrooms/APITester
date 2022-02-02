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
            $aa[] = $this->prepareForParameters($operation);
        }

        $aa = array_merge(...$aa);

        return array_merge(
            $aa,
//            $this->prepareForBody($api)
        );
    }

    /**
     * @return TestCase[]
     */
    private function prepareForParameters(Operation $operation): array
    {
        $requiredParams = [
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

        $parameterExamples = [];
        foreach ($requiredParams as $type => $parameters) {
            $parameterExamples[$type] = $this->getParameterExamples($parameters);
        }

        return array_merge(
            $this->prepareForQueryParams($requiredParams, $parameterExamples),
            $this->prepareForHeaders($requiredParams, $parameterExamples)
        );

//        $requests = $api->getOperations()
//            ->select('requests.*')
//            ->flatten()
//            ->toArray();
//
//        $requestBodies = [];
//        /** @var \OpenAPITesting\Definition\Request $request */
//        foreach ($requests as $request) {
//            $requestBody = [];
//            foreach ($request->getBody()->required as $requiredField) {
//                $requestBody[$requiredField] = $request->getExamples()->toArray()[0];
//            }
//            $requestBodies[] = $requestBody;
//        }

        return $testCases;
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
     *
     * @return TestCase[]
     */
    private function prepareForQueryParams(array $requiredParams, array $parameterExamples): array
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
                $parameterExamples[self::HEADER_PARAMETER_TYPE]
            );
        }

        return $testCases;
    }

    /**
     * @param array<string,Parameter[]> $requiredParams
     * @param array<string,ParameterExample[]> $parameterExamples
     *
     * @return TestCase[]
     */
    private function prepareForHeaders(array $requiredParams, array $parameterExamples): array
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
                $headers
            );
        }

        return $testCases;
    }

    /**
     * @param ParameterExample[] $pathParams
     * @param ParameterExample[] $queryParams
     * @param ParameterExample[] $headers
     */
    private function createForMissingParameter(
        Parameter $missing,
        array $pathParams,
        array $queryParams,
        array $headers
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
                $formattedHeaders
            ),
            new Response(400)
        );
    }

    /**
     * @return TestCase[]
     */
    private function prepareForBody(Api $api): array
    {
        $requests = $api->getOperations()
            ->select('requests.*')
            ->flatten()
            ->toArray();

        $testCases = [];
        /** @var \OpenAPITesting\Definition\Request $request */
        foreach ($requests as $request) {
            foreach ($request->getBody()->required as $requiredField) {
                $testCases[] = new TestCase();
            }
        }

        return [];
    }
}
