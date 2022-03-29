<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Operation;
use Illuminate\Support\Collection;

/**
 * @method Operation[] getIterator()
 * @method Operations  map(callable $c)
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
