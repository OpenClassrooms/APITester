<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Preparator\Config\PaginationErrorConfig;
use OpenAPITesting\Preparator\Config\PaginationErrorConfigItem;
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


    private PaginationErrorConfig $config;

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
            ->map(fn (Operation $operation) => $this->prepareTestCases($operation));

        return array_merge(...$testCases);
    }

    /**
     * @inheritDoc
     */
    public function configure(array $rawConfig): void
    {
        parent::configure($rawConfig);

        if (!isset($rawConfig['range'])) {
            throw new \InvalidArgumentException('A range config must be defined to use ' . __CLASS__);
        }

        $config = new PaginationErrorConfig();

        foreach ($rawConfig['range'] as $rawConfigItem) {
            $config->add(
                new PaginationErrorConfigItem(
                    $rawConfigItem['in'],
                    $rawConfigItem['names'],
                    $rawConfigItem['unit'] ?? null
                )
            );
        }

        $this->config = $config;
    }

    /**
     * @return TestCase[]
     */
    private function prepareTestCases(Operation $operation): array
    {
        $configItem = $this->getRangeConfig($operation);

        if (null === $configItem) {
            return [];
        }

        if ($configItem->isInQuery()) {
            return $this->prepareWithQueryParam($operation, $configItem);
        }

        if ($configItem->isInHeader()) {
            return $this->prepareWithHeader($operation, $configItem);
        }

        return [];
    }

    private function getRangeConfig(Operation $operation): ?PaginationErrorConfigItem
    {
        foreach ($this->config as $configItem) {
            if ($configItem->isInQuery()) {
                $lower = $operation->getQueryParameters()
                    ->where('name', $configItem->getLower())->first();
                $upper = $operation->getQueryParameters()
                    ->where('name', $configItem->getUpper())->first();

                if (null === $lower || null === $upper) {
                    continue;
                }

                return $configItem;
            }

            if ($configItem->isInHeader()) {
                $header = $operation->getHeaders()
                    ->where('name', $configItem->getNames()[0])->first();

                if (null === $header) {
                    continue;
                }

                return $configItem;
            }
        }

        return null;
    }

    /**
     * @return TestCase[]
     */
    private function prepareWithQueryParam(Operation $operation, PaginationErrorConfigItem $configItem): array
    {
        $testCases = [];
        foreach ([self::NON_NUMERIC_VALUES, self::INVERSED_VALUES, self::NEGATIVE_VALUES] as $values) {
            $testCases[] = new TestCase(
                $values['name'] . '_query_range_' . $operation->getId(),
                new Request(
                    $operation->getMethod(),
                    "{$operation->getPath()}?{$configItem->getLower()}={$values['lower']}&{$configItem->getUpper()}={$values['upper']}"
                ),
                new Response(416)
            );
        }

        return $testCases;
    }

    /**
     * @return TestCase[]
     */
    private function prepareWithHeader(Operation $operation, PaginationErrorConfigItem $configItem): array
    {
        $testCases = [];
        foreach ([self::NON_NUMERIC_VALUES, self::INVERSED_VALUES] as $values) {
            $testCases[] = new TestCase(
                $values['name'] . '_header_range_' . $operation->getId(),
                new Request(
                    $operation->getMethod(),
                    $operation->getPath(),
                    [
                        $configItem->getNames()[0] => "{$configItem->getUnit()}={$values['lower']}-{$values['upper']}",
                    ]
                ),
                new Response(416)
            );
        }

        return $testCases;
    }
}
