<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Loader\ExamplesExtensionLoader;
use APITester\Definition\Operation;
use APITester\Preparator\Config\ExamplesConfig;
use APITester\Preparator\Exception\PreparatorLoadingException;
use APITester\Test\TestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @property ExamplesConfig $config
 */
final class ExamplesPreparator extends TestCasesPreparator
{
    /**
     * @inheritdoc
     */
    protected function prepare(Operations $operations): iterable
    {
        $operations = $this->handleExtension($operations);

        /** @var iterable<array-key, TestCase> */
        return $operations
            ->map(fn (Operation $operation) => $this->prepareTestCases($operation))
            ->flatten()
        ;
    }

    /**
     * @throws PreparatorLoadingException
     */
    private function handleExtension(Operations $operations): Operations
    {
        if (null !== $this->config->extensionPath) {
            try {
                $operations = ExamplesExtensionLoader::load(
                    $this->config->extensionPath,
                    $operations
                );
            } catch (ExceptionInterface $e) {
                throw new PreparatorLoadingException(self::getName(), $e);
            }
        }

        return $operations;
    }

    /**
     * @return iterable<TestCase>
     */
    private function prepareTestCases(Operation $operation): iterable
    {
        return $operation
            ->getExamples()
            ->map(
                fn (OperationExample $example) => $this->buildTestCase(
                    $example->setAutoComplete(false)
                )
            )
        ;
    }
}
