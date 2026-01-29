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

    private function prepareTestCases(Operation $operation): OperationExamples
    {
        $examples = $operation->getExamples();
        if ($this->config->autoCreateWhenMissing && $examples->count() === 0) {
            $examples = new OperationExamples([
                $operation->getRandomExample(),
            ]);
        }

        return $examples
            ->map(
                function (OperationExample $example) {
                    if ($this->config->response->statusCode !== null) {
                        $example->setStatusCode($this->config->response->statusCode);
                    }
                    $example->setAutoComplete($this->config->autoComplete);

                    return $this->buildTestCase($example);
                }
            )
        ;
    }
}
