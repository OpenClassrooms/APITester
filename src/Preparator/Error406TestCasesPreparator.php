<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Operation;
use APITester\Preparator\Config\Error406;
use APITester\Test\TestCase;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

/**
 * @property Error406 $config
 */
final class Error406TestCasesPreparator extends TestCasesPreparator
{
    /**
     * @inheritdoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        /** @var TestCase[] */
        return $operations->map(
            fn (Operation $operation) => $operation->getResponses()
                ->select('mediaType')
                ->intersect($this->config->mediaTypes)
                ->compare($this->config->mediaTypes)
                ->shuffle()
                ->take($this->config->casesCount)
                ->sort()
                ->map(fn ($type) => $this->prepareTestCase(
                    $operation,
                    (string) $type
                ))
        )->flatten()
        ;
    }

    private function prepareTestCase(Operation $operation, string $type): TestCase
    {
        $request = new Request(
            $operation->getMethod(),
            $operation->getExamplePath(),
            [
                'Accept' => $type,
            ]
        );

        return $this->buildTestCase(
            $operation,
            $request,
            new Response(406)
        );
    }
}
