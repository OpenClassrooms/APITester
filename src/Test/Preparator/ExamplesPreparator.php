<?php

declare(strict_types=1);

namespace APITester\Test\Preparator;

use APITester\Schema\Entity\Collection\OperationExamples;
use APITester\Schema\Entity\Collection\Operations;
use APITester\Schema\Entity\Example\OperationExample;
use APITester\Schema\Entity\Operation;
use APITester\Schema\Loader\ExamplesExtensionLoader;
use APITester\Test\Entity\TestCase;
use APITester\Test\Preparator\Config\ExamplesPreparatorConfig;
use APITester\Test\Preparator\Exception\PreparatorLoadingException;
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
