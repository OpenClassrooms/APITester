<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
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
        return array_merge(
            $this->prepareForQueryParameters($api),
//            $this->prepareForBody($api)
        );
    }

    /**
     * @return TestCase[]
     */
    private function prepareForQueryParameters(Api $api): array
    {
        $requiredParams = [];
        $parameterExamples = [];
        foreach ([self::HEADER_PARAMETER_TYPE, self::PATH_PARAMETER_TYPE, self::QUERY_PARAMETER_TYPE] as $type) {
            $requiredParams[$type] = $api->getOperations()
                ->select($type . '.*')
                ->flatten()
                ->where('required', true)
                ->toArray();

            $parameterExamples[$type] = $this->getParameterExamples($requiredParams[$type]);
        }

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
     * @param Parameter[] $parameters
     *
     * @return ParameterExample[]
     */
    private function getParameterExamples(array $parameters): array
    {
        // Use ParameterExamples Collection instead of ParameterExample[] ?
        return array_map(static fn (Parameter $p): ParameterExample => $p->getExamples()->toArray()[0], $parameters);
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
            ->values();

        /** @var \OpenAPITesting\Definition\Request $request */
        foreach ($requests as $request) {
            foreach ($request->getBody()->required as $requiredField) {
            }
        }

        return [];
    }
}
