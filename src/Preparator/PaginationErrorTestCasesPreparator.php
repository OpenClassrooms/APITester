<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Preparator\Config\PaginationError;
use OpenAPITesting\Preparator\Config\Range;
use OpenAPITesting\Test\TestCase;

/**
 * @property PaginationError $config
 */
abstract class PaginationErrorTestCasesPreparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        $testCases = $operations
            ->map(fn (Operation $operation) => $this->prepareTestCases($operation))
        ;

        return array_merge(...$testCases);
    }

    protected static function getConfigClassName(): string
    {
        return 'PaginationError';
    }

    /**
     * @return string[][]
     */
    abstract protected function getQueryValues(): array;

    abstract protected function getErrorCode(): int;

    /**
     * @return string[][]
     */
    abstract protected function getHeaderValues(): array;

//    /**
//     * @inheritDoc
//     */
//    public function configure(Config\Preparator $config): void
//    {
//        parent::configure($config);
//
//        if (!isset($config['range'])) {
//            throw new \InvalidArgumentException('A range config must be defined to use ' . __CLASS__);
//        }
//
//        if (!\is_array($config['range'])) {
//            throw new \InvalidArgumentException('Range config must be an array to use ' . __CLASS__);
//        }
//
//        $config = new PaginationError();
//
//        foreach ($config['range'] as $rawConfigItem) {
//            $config->addItem(
//                new Range(
//                    $rawConfigItem['in'],
//                    $rawConfigItem['names'],
//                    $rawConfigItem['unit'] ?? null
//                )
//            );
//        }
//
//        $this->config = $config;
//    }

    /**
     * @return TestCase[]
     */
    private function prepareTestCases(Operation $operation): array
    {
        $configItem = $this->getRangeConfig($operation);

        if (null === $configItem) {
            return [];
        }

        if ($configItem->inQuery()) {
            return $this->prepareWithQueryParam($operation, $configItem);
        }

        if ($configItem->inHeader()) {
            return $this->prepareWithHeader($operation, $configItem);
        }

        return [];
    }

    private function getRangeConfig(Operation $operation): ?Range
    {
        foreach ($this->config->getRange() as $configItem) {
            if ($configItem->inQuery()) {
                $lower = $operation->getQueryParameters()
                    ->where('name', $configItem->getLower())
                    ->first()
                ;
                $upper = $operation->getQueryParameters()
                    ->where('name', $configItem->getUpper())
                    ->first()
                ;

                if (null === $lower || null === $upper) {
                    continue;
                }

                return $configItem;
            }

            if ($configItem->inHeader()) {
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
    private function prepareWithQueryParam(Operation $operation, Range $configItem): array
    {
        $testCases = [];
        foreach ($this->getQueryValues() as $values) {
            $testCases[] = new TestCase(
                $values['name'] . '_query_range_' . $operation->getId(),
                new Request(
                    $operation->getMethod(),
                    "{$operation->getPath()}?{$configItem->getLower()}={$values['lower']}&{$configItem->getUpper()}={$values['upper']}"
                ),
                new Response($this->getErrorCode())
            );
        }

        return $testCases;
    }

    /**
     * @return TestCase[]
     */
    private function prepareWithHeader(Operation $operation, Range $configItem): array
    {
        $testCases = [];
        foreach ($this->getHeaderValues() as $values) {
            $testCases[] = new TestCase(
                $values['name'] . '_header_range_' . $operation->getId(),
                new Request(
                    $operation->getMethod(),
                    $operation->getPath(),
                    [
                        $configItem->getNames()[0] => "{$configItem->getUnit()}={$values['lower']}-{$values['upper']}",
                    ]
                ),
                new Response($this->getErrorCode())
            );
        }

        return $testCases;
    }
}
