<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Test\TestCase;

final class Error416TestCasesPreparator extends TestCasesPreparator
{
    /**
     * @var array
     */
    private $rangeConfig;

    public static function getName(): string
    {
        return '416';
    }

    /**
     * @inheritDoc
     */
    public function prepare(Api $api): array
    {
        $testCases = [];
        foreach ($api->getOperations() as $operation) {
            $rangeParameters = $this->getRangeParameters($operation);
            foreach ($rangeParameters as $rangeParameter) {
                $testCases[] = $this->prepareWithNegativeRange($operation, $rangeParameter);
                $testCases[] = $this->prepareWithNonNumericRange($operation, $rangeParameter);
                $testCases[] = $this->prepareWithInversedRange($operation, $rangeParameter);
            }
        }

        return $testCases;
    }

    /**
     * @inheritDoc
     */
    public function configure(array $config): void
    {
        parent::configure($config);

        $this->rangeConfig = $config['range'];
    }

    private function prepareWithNegativeRange(Operation $operation, array $rangeParameter): ?TestCase
    {
        // Negative range cannot be handled in header range format
        if ('query' !== $rangeParameter['in']) {
            return null;
        }

        return new TestCase(
            'negative_range_' . $operation->getDescription(),
            new Request(
                $operation->getMethod(),
                "{$operation->getPath()}?{$rangeParameter['name']['lower']}=-5&{$rangeParameter['name']['upper']}=5"
            ),
            new Response(416)
        );
    }

    private function getRangeParameters(Operation $operation): array
    {
        $parameters = [];
        foreach ($this->rangeConfig as $rangeParam) {
            $names = is_array($rangeParam['name']) ? $rangeParam['name'] : [$rangeParam['name']];
            foreach ($names as $name) {
                if (!$operation->findParameter($name, $rangeParam['in'])) {
                    continue 2;
                }
            }
            $parameters[] = $rangeParam;
        }

        return $parameters;
    }
}
