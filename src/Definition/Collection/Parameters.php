<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Parameter;
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
     * @return array<string, string|int>
     */
    public function getExamples(): array
    {
        $examples = [];
        foreach ($this->items as $item) {
            $example = $item->getExample();
            if ($example !== null) {
                $examples[$item->getName()] = $example;
            }
        }

        return $examples;
    }

    /**
     * @return array<string, string>
     */
    public function getRandomExamples(): array
    {
        $params = [];
        foreach ($this as $parameter) {
            $schema = $parameter->getSchema();
            if ($schema !== null) {
                $random = (new SchemaFaker($schema, new Options()))->generate();
            } else {
                try {
                    $random = base64_encode(random_bytes(30));
                } catch (\Exception $e) {
                    throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
                }
            }

            $params[$parameter->getName()] = \is_array($random)
                ? implode(',', $random)
                : (string) $random;
        }

        return $params;
    }
}
