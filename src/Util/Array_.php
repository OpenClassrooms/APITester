<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

final class Array_
{
    /**
     * @template Tk of array-key
     * @template Tv
     *
     * @param array<Tk, Tv> $first
     * @param array<Tk, Tv> ...$rest
     *
     * @return array<Tk, mixed>
     */
    public static function merge(array $first, array ...$rest): array
    {
        $merged = $first;
        foreach ($rest as $array) {
            foreach ($array as $key => $value) {
                if (\is_array($value)
                    && \array_key_exists($key, $merged)
                    && \is_array($merged[$key])
                ) {
                    $merged[$key] = self::merge($merged[$key], $value);
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * @template T
     *
     * @param array<T> $items
     *
     * @return array<T>
     */
    public static function pickRandomItems(array $items, int $count): array
    {
        /** @var int[] $randomIndexes */
        $randomIndexes = array_rand($items, $count);

        return array_filter(
            $items,
            static fn ($i) => \in_array($i, $randomIndexes, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
