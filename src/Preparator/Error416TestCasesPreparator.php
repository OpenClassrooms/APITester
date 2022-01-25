<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Test\TestCase;

final class Error416TestCasesPreparator extends TestCasesPreparator
{
    public const HEADER_RANGE = 'header';
    public const QUERY_PARAM_RANGE = 'query';
    public const NEGATIVE_VALUES = [
        'name' => 'negative',
        'lower' => '-5',
        'upper' => '5',
    ];
    public const NON_NUMERIC_VALUES = [
        'name' => 'non_numeric',
        'lower' => 'toto',
        'upper' => 'tata',
    ];
    public const INVERSED_VALUES = [
        'name' => 'inversed',
        'lower' => '20',
        'upper' => '5',
    ];

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
        $testCases = $api->getOperations()
            ->map(fn (Operation $operation) => $this->prepareTestCases($operation));

        return array_merge(...$testCases);
    }

    /**
     * @inheritDoc
     */
    public function configure(array $config): void
    {
        parent::configure($config);

        $this->rangeConfig = $config['range'] ?? null;
    }

    /**
     * @return TestCase[]
     */
    private function prepareTestCases(Operation $operation): array
    {
        $rangeConfig = $this->getRangeConfig($operation);

        if (null === $rangeConfig) {
            return [];
        }

        $rangeConfig = $this->prepareValues($rangeConfig);

        switch ($rangeConfig['in']) {
            case self::QUERY_PARAM_RANGE:
                return $this->prepareWithQueryParam($operation, $rangeConfig);
            case self::HEADER_RANGE:
                return $this->prepareWithHeader($operation, $rangeConfig);
            default:
                return [];
        }
    }

    private function getRangeConfig(Operation $operation): ?array
    {
        if (null === $this->rangeConfig) {
            return null;
        }

        foreach ($this->rangeConfig as $rangeConfig) {
            if (self::QUERY_PARAM_RANGE === $rangeConfig['in']) {
                $lower = $operation->getQueryParameters()->where('name', $rangeConfig['lower'])->first();
                $upper = $operation->getQueryParameters()->where('name', $rangeConfig['upper'])->first();

                if (null === $lower || null === $upper) {
                    continue;
                }

                return $rangeConfig;
            }

            if (self::HEADER_RANGE === $rangeConfig['in']) {
                $header = $operation->getHeaders()->where('name', $rangeConfig['name'])->first();

                if (null === $header) {
                    continue;
                }

                return $rangeConfig;
            }
        }

        return null;
    }

    private function prepareValues(array $rangeConfig): array
    {
        $rangeConfig['values'] = [
            self::NON_NUMERIC_VALUES,
            self::INVERSED_VALUES,
        ];

        if (self::QUERY_PARAM_RANGE === $rangeConfig['in']) {
            $rangeConfig['values'][] = self::NEGATIVE_VALUES;
        }

        return $rangeConfig;
    }

    /**
     * @return TestCase[]
     */
    private function prepareWithQueryParam(Operation $operation, array $rangeConfig): array
    {
        $testCases = [];

        foreach ($rangeConfig['values'] as $values) {
            $testCases[] = new TestCase(
                $values['name'] . '_query_range_' . $operation->getId(),
                new Request(
                    $operation->getMethod(),
                    "{$operation->getPath()}?{$rangeConfig['lower']}={$values['lower']}&{$rangeConfig['upper']}={$values['upper']}"
                ),
                new Response(416)
            );
        }

        return $testCases;
    }

    /**
     * @return TestCase[]
     */
    private function prepareWithHeader(Operation $operation, array $rangeConfig): array
    {
        $testCases = [];

        foreach ($rangeConfig['values'] as $values) {
            $testCases[] = new TestCase(
                $values['name'] . '_header_range_' . $operation->getId(),
                new Request(
                    $operation->getMethod(),
                    $operation->getPath(),
                    [
                        $rangeConfig['name'] => "{$rangeConfig['unit']}={$values['lower']}-{$values['upper']}",
                    ]
                ),
                new Response(416)
            );
        }

        return $testCases;
    }
}
