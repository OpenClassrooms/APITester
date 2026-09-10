<?php

declare(strict_types=1);

namespace APITester\Test\Preparator;

use APITester\Schema\Entity\Collection\Operations;
use APITester\Schema\Entity\Operation;
use APITester\Test\Entity\TestCase;
use APITester\Test\Preparator\Config\RandomPreparatorConfig;

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
