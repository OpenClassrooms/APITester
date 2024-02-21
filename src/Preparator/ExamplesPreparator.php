<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\OperationExamples;
use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Loader\ExamplesExtensionLoader;
use APITester\Definition\Operation;
use APITester\Preparator\Config\ExamplesPreparatorConfig;
use APITester\Preparator\Exception\PreparatorLoadingException;
use APITester\Test\TestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @property ExamplesPreparatorConfig $config
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
        if ($this->config->extensionPath !== null) {
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
        $examples = $operation->getExamples();
        if ($this->config->autoCreateWhenMissing && $examples->count() > 0) {
            $examples = new OperationExamples([
                $operation->getRandomExample(),
            ]);
        }

        return $examples
            ->map(
                fn (OperationExample $example) => $this->buildTestCase(
                    $example->setAutoComplete($this->config->autoComplete)
                )
            )
        ;
    }
}
