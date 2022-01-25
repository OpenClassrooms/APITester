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
        'lower' => 'foo',
        'upper' => 'bar',
    ];
    public const INVERSED_VALUES = [
        'name' => 'inversed',
        'lower' => '20',
        'upper' => '5',
    ];

    /**
     * @var array<array{'in':string,'name'?:string, 'unit'?:string, 'upper'?:string, 'lower'?:string}>|null
     */
    private ?array $rangeConfig;

    public static function getName(): string
    {
        return '416';
    }

    /**
     * @inheritDoc
     */
    public function prepare(Api $api): iterable
    {
        $testCases = $api->getOperations()
            ->map(fn (Operation $operation) => $this->prepareTestCases($operation))
        ;

        return array_merge(...$testCases);
    }

    /**
     * @inheritDoc
     */
    public function configure(array $config): void
    {
        parent::configure($config);

        /** @var array<array{'in':string,'name'?:string, 'unit'?:string, 'upper'?:string, 'lower'?:string}>|null $rangeConfig */
        $rangeConfig = $config['range'] ?? null;

        $this->rangeConfig = $rangeConfig;
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

        if (self::QUERY_PARAM_RANGE === $rangeConfig['in']) {
            return $this->prepareWithQueryParam($operation, $rangeConfig);
        }

        if (self::HEADER_RANGE === $rangeConfig['in']) {
            return $this->prepareWithHeader($operation, $rangeConfig);
        }

        return [];
    }

    /**
     * @return array{'in':string,'name'?:string, 'unit'?:string, 'upper'?:string, 'lower'?:string}|null
     */
    private function getRangeConfig(Operation $operation): ?array
    {
        if (null === $this->rangeConfig) {
            return null;
        }

        foreach ($this->rangeConfig as $rangeConfig) {
            if (self::QUERY_PARAM_RANGE === $rangeConfig['in']) {
                if (!isset($rangeConfig['lower'], $rangeConfig['upper'])) {
                    continue;
                }

                $lower = $operation->getQueryParameters()
                    ->where('name', $rangeConfig['lower'])->first();
                $upper = $operation->getQueryParameters()
                    ->where('name', $rangeConfig['upper'])->first();

                if (null === $lower || null === $upper) {
                    continue;
                }

                return $rangeConfig;
            }

            if (self::HEADER_RANGE === $rangeConfig['in']) {
                if (!isset($rangeConfig['name'])) {
                    continue;
                }

                $header = $operation->getHeaders()
                    ->where('name', $rangeConfig['name'])->first();

                if (null === $header) {
                    continue;
                }

                return $rangeConfig;
            }
        }

        return null;
    }

    /**
     * @param array{'in':string,'name'?:string, 'unit'?:string, 'upper'?:string, 'lower'?:string} $rangeConfig
     *
     * @return TestCase[]
     */
    private function prepareWithQueryParam(Operation $operation, array $rangeConfig): array
    {
        if (!isset($rangeConfig['lower'], $rangeConfig['upper'])) {
            return [];
        }

        $testCases = [];
        foreach ([self::NEGATIVE_VALUES, self::NON_NUMERIC_VALUES, self::INVERSED_VALUES] as $values) {
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
     * @param array{'in':string,'name'?:string, 'unit'?:string, 'upper'?:string, 'lower'?:string} $rangeConfig
     *
     * @return TestCase[]
     */
    private function prepareWithHeader(Operation $operation, array $rangeConfig): array
    {
        if (!isset($rangeConfig['name'], $rangeConfig['unit'])) {
            return [];
        }

        $testCases = [];
        foreach ([self::NON_NUMERIC_VALUES, self::INVERSED_VALUES] as $values) {
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
