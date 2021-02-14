<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

class Array_
{
    public static function merge(array $haystack, array ...$needle): array
    {
        $merged = $haystack;

        foreach ($needle as $array) {
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                    $merged[$key] = self::merge($merged[$key], $value);
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }
}
