<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Util\Collection;

/**
 * @method Parameter[] getIterator()
 * @extends Collection<array-key, Parameter>
 */
final class Parameters extends Collection
{
    /**
     * @return array<string, string>
     */
    public function toExampleArray(): array
    {
        $params = [];
        foreach ($this->items as $item) {
            /** @var ParameterExample|null $example */
            $example = $item->getExamples()
                ->first()
            ;
            if (null !== $example) {
                $params[$item->getName()] = $example->getValue();
            }
        }

        return $params;
    }
}
