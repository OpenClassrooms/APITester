<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

final class Array_
{
    /**
     * @param mixed[] $haystack
     * @param mixed[] $needle
     *
     * @return mixed[]
     */
    public static function merge(array $haystack, array ...$needle): array
    {
        $merged = $haystack;

        foreach ($needle as $array) {
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
}
