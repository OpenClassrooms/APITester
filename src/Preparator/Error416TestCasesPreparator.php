<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Test\TestCase;

final class Error416TestCasesPreparator extends TestCasesPreparator
{
    private ?array $rangeConfig;

    public static function getName(): string
    {
        return '416';
    }

    /**
     * @inheritDoc
     */
    public function prepare(Api $api): array
    {
        $testCases = [];
        foreach ($api->getOperations() as $operation) {
            $rangeQueryParameters = $this->getRangeQueryParameters($operation);
            $rangeHeaders = $this->getRangeHeaders($operation);
            foreach ($rangeQueryParameters as $parameter) {
                $testCases[] = $this->prepareWithNegativeQueryParam($operation, $parameter);
                $testCases[] = $this->prepareWithNonNumericQueryParam($operation, $parameter);
                $testCases[] = $this->prepareWithInversedRangeQueryParam($operation, $parameter);
            }
            foreach ($rangeHeaders as $header) {
                $testCases[] = $this->prepareWithNonNumericHeader($operation, $header);
                $testCases[] = $this->prepareWithInversedRangeHeader($operation, $header);
            }
        }

        return $testCases;
    }

    /**
     * @inheritDoc
     */
    public function configure(array $config): void
    {
        parent::configure($config);

        $this->rangeConfig = $config['range'] ?? null;
    }

    private function getRangeQueryParameters(Operation $operation): array
    {
        if (!isset($this->rangeConfig['query'])) {
            return [];
        }

        $parameters = [];
        foreach ($this->rangeConfig['query'] as $parameterSet) {
            $lower = $operation->getQueryParameters()->where('name', $parameterSet['lower'])->first();
            $upper = $operation->getQueryParameters()->where('name', $parameterSet['upper'])->first();

            if (null === $lower || null === $upper) {
                continue;
            }

            $parameters[] = [
                'lower' => $lower,
                'upper' => $upper,
            ];
        }

        return $parameters;
    }

    private function getRangeHeaders(Operation $operation): array
    {
        if (!isset($this->rangeConfig['header'])) {
            return [];
        }

        $parameters = [];
        foreach ($this->rangeConfig['header'] as $headerConfig) {
            $header = $operation->getHeaders()->where('name', $headerConfig['name'])->first();

            if (null === $header) {
                continue;
            }

            $parameters[] = $headerConfig;
        }

        return $parameters;
    }

    /**
     * @param Parameter[] $rangeParameter
     */
    private function prepareWithNegativeQueryParam(Operation $operation, array $rangeParameter): TestCase
    {
        return new TestCase(
            'negative_query_range_' . $operation->getId(),
            new Request(
                $operation->getMethod(),
                "{$operation->getPath()}?{$rangeParameter['lower']->getName()}=-5&{$rangeParameter['upper']->getName()}=5"
            ),
            new Response(416)
        );
    }

    /**
     * @param Parameter[] $parameters
     */
    private function prepareWithNonNumericQueryParam(Operation $operation, array $parameters): TestCase
    {
        return new TestCase(
            'non_numeric_query_range_' . $operation->getId(),
            new Request(
                $operation->getMethod(),
                "{$operation->getPath()}?{$parameters['lower']->getName()}=toto&{$parameters['upper']->getName()}=tata"
            ),
            new Response(416)
        );
    }

    /**
     * @param Parameter[] $parameters
     */
    private function prepareWithInversedRangeQueryParam(Operation $operation, array $parameters): TestCase
    {
        return new TestCase(
            'inversed_query_range_' . $operation->getId(),
            new Request(
                $operation->getMethod(),
                "{$operation->getPath()}?{$parameters['lower']->getName()}=20&{$parameters['upper']->getName()}=5"
            ),
            new Response(416)
        );
    }

    private function prepareWithNonNumericHeader(Operation $operation, array $header): TestCase
    {
        return new TestCase(
            'non_numeric_header_range_' . $operation->getId(),
            new Request(
                $operation->getMethod(),
                $operation->getPath(),
                [
                    $header['name'] => "{$header['unit']}=toto-tata",
                ]
            ),
            new Response(416)
        );
    }

    private function prepareWithInversedRangeHeader(Operation $operation, array $header): TestCase
    {
        return new TestCase(
            'inversed_header_range_' . $operation->getId(),
            new Request(
                $operation->getMethod(),
                $operation->getPath(),
                [
                    $header['name'] => "{$header['unit']}=20-5",
                ]
            ),
            new Response(416)
        );
    }
}
