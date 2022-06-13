<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Operation;
use APITester\Preparator\Config\RandomPreparatorConfig;
use APITester\Test\TestCase;

/**
 * @property RandomPreparatorConfig $config
 */
final class RandomPreparator extends TestCasesPreparator
{
    /**
     * @inheritdoc
     */
    protected function prepare(Operations $operations): iterable
    {
        /** @var iterable<array-key, TestCase> */
        return $operations
            ->map(fn (Operation $operation) => $this->prepareTestCases($operation))
            ->flatten()
        ;
    }

    /**
     * @return iterable<TestCase>
     */
    private function prepareTestCases(Operation $operation): iterable
    {
        $testCases = [];
        foreach (range(1, $this->config->casesCount) as $ignored) {
            $random = $operation->getRandomExample();
            $random->setStatusCode($this->config->response->getStatusCode() ?? '#^(?!500)\d+#');
            $testcase = $this->buildTestCase($random);
            $testCases[$testcase->getHash()] = $testcase;
        }

        return $testCases;
    }
}
