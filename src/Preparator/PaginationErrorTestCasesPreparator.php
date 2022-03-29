<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Operation;
use APITester\Preparator\Config\PaginationError;
use APITester\Preparator\Config\PaginationError\Range;
use APITester\Preparator\Exception\PreparatorLoadingException;
use APITester\Test\TestCase;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

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
        /** @var TestCase[] */
        return $operations
            ->map(fn (Operation $operation) => $this->prepareTestCases($operation))
            ->flatten()
        ;
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

    /**
     * @throws PreparatorLoadingException
     *
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

    /**
     * @throws PreparatorLoadingException
     */
    private function getRangeConfig(Operation $operation): ?Range
    {
        if (null === $this->config) {
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

                if (null === $lower || null === $upper) {
                    continue;
                }

                return $configItem;
            }

            if ($configItem->inHeader()) {
                $header = $operation->getHeaders()
                    ->where('name', $configItem->getNames()[0])->first()
                ;

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
    private function prepareWithQueryParam(Operation $operation, Config\PaginationError\Range $configItem): array
    {
        $testCases = [];
        foreach ($this->getQueryValues() as $values) {
            $request = new Request(
                $operation->getMethod(),
                "{$operation->getPath()}?{$configItem->getLower()}={$values['lower']}&{$configItem->getUpper()}={$values['upper']}"
            );
            $testCases[] = $this->buildTestCase(
                $operation,
                $request,
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
            $request = new Request(
                $operation->getMethod(),
                $operation->getPath(),
                [
                    $configItem->getNames()[0] => "{$configItem->getUnit()}={$values['lower']}-{$values['upper']}",
                ]
            );
            $request = $this->authenticate($request, $operation);
            $testCases[] = $this->buildTestCase(
                $operation,
                $request,
                new Response($this->getErrorCode())
            );
        }

        return $testCases;
    }
}
