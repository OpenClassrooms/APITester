<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements \Illuminate\Support\Collection<TKey, TValue>
 */
class Collection extends \Illuminate\Support\Collection
{
    /**
     * @var array<TKey, TValue>
     */
    protected $items;

    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    /**
     * @param TKey $key
     *
     * @return TValue
     */
    public function offsetGet($key)
    {
        return parent::offsetGet($key);
    }

    /**
     * @param TKey|null $key
     * @param TValue $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        parent::offsetSet($key, $value);
    }

    /**
     * @param TKey $key
     */
    public function offsetExists($key)
    {
        return parent::offsetExists($key);
    }

    /**
     * @param TKey $key
     */
    public function offsetUnset($key)
    {
        parent::offsetUnset($key);
    }

    /**
     * @template TMapValue
     *
     * @param callable(TValue, ?TKey): TMapValue $callback
     *
     * @return static<TKey, TMapValue>
     */
    public function map(callable $callback): self
    {
        return parent::map($callback);
    }

    /**
     * @param int|null $number
     *
     * @return static<TKey, TValue>|TValue
     */
    public function random($number = null): self
    {
        return parent::random($number);
    }

    /**
     * @param TValue $item
     *
     * @return static<TKey, TValue>
     */
    public function add($item): self
    {
        return parent::add($item);
    }

    /**
     * @template TGetDefault
     *
     * @param TKey $key
     * @param TGetDefault $default
     *
     * @return TValue|TGetDefault
     */
    public function get($key, $default = null)
    {
        return parent::get($key, $default);
    }

    /**
     * @param array|callable|string $groupBy
     * @param bool $preserveKeys
     *
     * @return static<static<TKey, TValue>>
     */
    public function groupBy($groupBy, $preserveKeys = false): self
    {
        return parent::groupBy($groupBy, $preserveKeys);
    }

    /**
     * @param string|array|int|null $value
     *
     * @return static<TKey, mixed>
     */
    public function select($value, ?string $index = null): self
    {
        return $this->pluck($value, $index);
    }

    /**
     * @param iterable<array-key, TValue> $items
     *
     * @return static<TKey, TValue>
     */
    public function compare($items): self
    {
        return $this->diff($items)
            ->merge(collect($items)->diff($this))
        ;
    }
}
