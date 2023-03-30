<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Preparator\Config\PaginationError\RangeConfig;
use APITester\Preparator\Config\PaginationPreparatorErrorConfig;
use APITester\Preparator\Exception\PreparatorLoadingException;
use APITester\Test\TestCase;

/**
 * @property PaginationPreparatorErrorConfig $config
 */
abstract class PaginationErrorPreparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var iterable<array-key, TestCase> */
        return $operations
            ->map(fn (Operation $operation) => $this->prepareTestCases($operation))
            ->flatten()
        ;
    }

    protected static function getConfigClassName(): string
    {
        return 'PaginationPreparatorErrorConfig';
    }

    /**
     * @return string[][]
     */
    abstract protected function getQueryValues(): array;

    abstract protected function getStatusCode(): string;

    /**
     * @return string[][]
     */
    abstract protected function getHeaderValues(): array;

    /**
     * @throws PreparatorLoadingException
     *
     * @return TestCase[]
     */
    private function prepareTestCases(Operation $operation): array
    {
        $configItem = $this->getRangeConfig($operation);

        if ($configItem === null) {
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

    /**
     * @throws PreparatorLoadingException
     */
    private function getRangeConfig(Operation $operation): ?RangeConfig
    {
        if ($this->config === null) {
            throw new PreparatorLoadingException(self::getName());
        }
        foreach ($this->config->range as $configItem) {
            if ($configItem->inQuery()) {
                $lower = $operation->getQueryParameters()
                    ->where('name', $configItem->getLower())
                    ->first()
                ;
                $upper = $operation->getQueryParameters()
                    ->where('name', $configItem->getUpper())
                    ->first()
                ;

                if ($lower === null || $upper === null) {
                    continue;
                }

                return $configItem;
            }

            if ($configItem->inHeader()) {
                $header = $operation->getHeaders()
                    ->where('name', $configItem->getNames()[0])->first()
                ;

                if ($header === null) {
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
    private function prepareWithQueryParam(Operation $operation, Config\PaginationError\RangeConfig $configItem): array
    {
        $testCases = [];
        foreach ($this->getQueryValues() as $values) {
            $testCases[] = $this->buildTestCase(
                OperationExample::create($values['name'], $operation)
                    ->setQueryParameters([
                        $configItem->getLower() => $values['lower'],
                        $configItem->getUpper() => $values['upper'],
                    ])
                    ->setResponse(ResponseExample::create()->setStatusCode($this->getStatusCode()))
            );
        }

        return $testCases;
    }

    /**
     * @return TestCase[]
     */
    private function prepareWithHeader(Operation $operation, RangeConfig $configItem): array
    {
        $testCases = [];
        foreach ($this->getHeaderValues() as $values) {
            $testCases[] = $this->buildTestCase(
                OperationExample::create($values['name'], $operation)
                    ->setHeaders([
                        $configItem->getNames()[0] => "{$configItem->getUnit()}={$values['lower']}-{$values['upper']}",
                    ])
                    ->setResponse(ResponseExample::create()->setStatusCode($this->getStatusCode()))
            );
        }

        return $testCases;
    }
}
