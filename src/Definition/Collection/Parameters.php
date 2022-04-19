<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Parameter;
use APITester\Definition\ParameterExample;
use Illuminate\Support\Collection;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

/**
 * @method Parameter[] getIterator()
 * @method Parameters  map(callable $c)
 * @extends Collection<array-key, Parameter>
 */
final class Parameters extends Collection
{
    /**
     * @var Parameter[]
     */
    protected $items;

    /**
     * @return array<string, string>
     */
    public function toExampleArray(): array
    {
        $params = [];
        foreach ($this->getExamples() as $example) {
            $params[$example->getParent()->getName()] = $example->getValue();
        }

        return $params;
    }

    public function getExamples(): ParameterExamples
    {
        $examples = [];
        foreach ($this->items as $item) {
            /** @var ParameterExample|null $example */
            $example = $item->getExamples()
                ->first()
            ;
            if (null !== $example) {
                $examples[] = $example;
            }
        }

        return new ParameterExamples($examples);
    }

    /**
     * @return array<string, string>
     */
    public function toRandomArray(): array
    {
        $params = [];
        foreach ($this as $parameter) {
            $schema = $parameter->getSchema();
            if (null !== $schema) {
                /** @var string|int $random */
                $random = (new SchemaFaker(
                    $schema,
                    new Options(),
                ))->generate();
            } else {
                try {
                    $random = base64_encode(random_bytes(30));
                } catch (\Exception $e) {
                    throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
                }
            }

            $params[$parameter->getName()] = (string) $random;
        }

        return $params;
    }
}
