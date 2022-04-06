<?php

declare(strict_types=1);

namespace APITester\Preparator;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Operation;
use APITester\Preparator\Config\Error405;
use APITester\Test\TestCase;
use APITester\Util\Json;
use Illuminate\Support\Collection;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

/**
 * @property Error405 $config
 */
final class Error405TestCasesPreparator extends TestCasesPreparator
{
    /**
     * @inheritdoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        /** @var TestCase[] */
        return $operations->groupBy('path', true)
            ->map(fn (Collection $pathOperations) => $pathOperations
            ->select('method')
            ->intersect($this->config->methods)
            ->compare($this->config->methods)
            ->crossJoin($pathOperations->take(1))
            ->map(
                function (array $data) {
                    /** @var array{0: string, 1: Operation} $data */
                    return $this->prepareTestCase($data[1], $data[0]);
                }
            ))
            ->flatten()
        ;
    }

    private function prepareTestCase(Operation $operation, string $method): TestCase
    {
        return $this->buildTestCase(
            $operation,
            new Request(
                $method,
                $operation->getExamplePath()
            ),
            new Response(
                $this->config->response->statusCode ?? 405,
                [],
                null !== $this->config->response ? Json::encode($this->config->response) : null,
            )
        );
    }
}
