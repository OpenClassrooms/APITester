<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Util\Collection;

/**
 * @method Operation[] getIterator()
 * @extends Collection<array-key, Operation>
 */
final class Operations extends Collection
{
    /**
     * @return array<string, array<Operation>>
     */
    public function toPropIndexedArray(): array
    {
        $operations = [];
        foreach ($this as $operation) {
            $operations[$operation->getMethod()][] = $operation;
            $operations[$operation->getId()][] = $operation;
            foreach ($operation->getTags()->select('name') as $tag) {
                $operations[(string) $tag][] = $operation;
            }
        }

        return $operations;
    }
}
