<?php
declare(strict_types=1);

namespace APITester\Definition\Collection;

trait Comparable
{
    /**
     * @param iterable<array-key, TValue> $items
     *
     * @return static<TKey, TValue>
     */
    public function compare(iterable $items): self
    {
        return $this->diff($items)
            ->merge(collect($items)->diff($this))
        ;
    }
}
