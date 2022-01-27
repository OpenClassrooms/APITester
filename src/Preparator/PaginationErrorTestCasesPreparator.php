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

abstract class PaginationErrorTestCasesPreparator extends TestCasesPreparator
{
    private PaginationErrorConfig $config;

    abstract public static function getName(): string;

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
    public function configure(array $rawConfig): void
    {
        parent::configure($rawConfig);

        if (!isset($rawConfig['range'])) {
            throw new \InvalidArgumentException('A range config must be defined to use ' . __CLASS__);
        }

        if (!\is_array($rawConfig['range'])) {
            throw new \InvalidArgumentException('Range config must be an array to use ' . __CLASS__);
        }

        $config = new PaginationErrorConfig();

        foreach ($rawConfig['range'] as $rawConfigItem) {
            $config->addItem(
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
     * @return string[][]
     */
    abstract protected function getQueryValues(): array;

    abstract protected function getErrorCode(): int;

    /**
     * @return string[][]
     */
    abstract protected function getHeaderValues(): array;

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

    private function getRangeConfig(Operation $operation): ?PaginationErrorConfigItem
    {
        foreach ($this->config->getItems() as $configItem) {
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
    private function prepareWithQueryParam(Operation $operation, PaginationErrorConfigItem $configItem): array
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
    private function prepareWithHeader(Operation $operation, PaginationErrorConfigItem $configItem): array
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
