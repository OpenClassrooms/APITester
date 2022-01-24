<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/symfony/property-access/PropertyAccessorBuilder.php';
require_once dirname(__DIR__, 2) . '/vendor/symfony/property-access/PropertyAccess.php';
require_once dirname(__DIR__, 2) . '/vendor/symfony/property-access/PropertyAccessorInterface.php';
require_once dirname(__DIR__, 2) . '/vendor/symfony/property-access/PropertyAccessor.php';

use Illuminate\Support\Arr;
use OpenAPITesting\Util\Accessor;
use OpenAPITesting\Util\Collection;

/**
 * @param mixed $value
 */
function collect($value = null): Collection
{
    return new Collection($value);
}

/**
 * Get an item from an array or object using "dot" notation.
 *
 * @param mixed                 $target
 * @param string|array|int|null $key
 * @param mixed                 $default
 *
 * @return mixed
 */
function data_get($target, $key, $default = null)
{
    if (null === $key) {
        return $target;
    }

    $key = is_array($key) ? $key : explode('.', (string) $key);

    foreach ($key as $i => $segment) {
        unset($key[$i]);

        if (null === $segment) {
            return $target;
        }

        if ('*' === $segment) {
            if ($target instanceof Collection) {
                $target = $target->all();
            } elseif (!is_array($target)) {
                return value($default);
            }

            $result = [];

            foreach ($target as $item) {
                $result[] = data_get($item, $key);
            }

            return in_array('*', $key, true) ? Arr::collapse($result) : $result;
        }

        if (Arr::accessible($target) && Arr::exists($target, $segment)) {
            $target = $target[$segment];
        } elseif (is_object($target) && isset($target->{$segment})) {
            $target = $target->{$segment};
        } elseif (is_object($target)) {
            $target = Accessor::get($target, $segment);
        } else {
            return value($default);
        }
    }

    return $target;
}

/**
 * Set an item on an array or object using dot notation.
 *
 * @param mixed        $target
 * @param string|array $key
 * @param mixed        $value
 *
 * @return mixed
 */
function data_set(&$target, $key, $value, bool $overwrite = true)
{
    $segments = is_array($key) ? $key : explode('.', $key);
    $segment = array_shift($segments);
    if ('*' === $segment) {
        if (!Arr::accessible($target)) {
            $target = [];
        }

        if (count($segments) > 0) {
            foreach ($target as &$inner) {
                data_set($inner, $segments, $value, $overwrite);
            }
        } elseif ($overwrite) {
            foreach ($target as &$inner) {
                $inner = $value;
            }
        }
    } elseif (Arr::accessible($target)) {
        if (count($segments) > 0) {
            if (!Arr::exists($target, $segment)) {
                $target[$segment] = [];
            }

            data_set($target[$segment], $segments, $value, $overwrite);
        } elseif ($overwrite || !Arr::exists($target, $segment)) {
            $target[$segment] = $value;
        }
    } elseif (is_object($target)) {
        if (count($segments) > 0) {
            if (!isset($target->{$segment})) {
                $target->{$segment} = [];
            }

            data_set($target->{$segment}, $segments, $value, $overwrite);
        } elseif ($overwrite || !isset($target->{$segment})) {
            Accessor::set($target, $segment, $value);
        }
    } else {
        $target = [];

        if (count($segments) > 0) {
            data_set($target[$segment], $segments, $value, $overwrite);
        } elseif ($overwrite) {
            $target[$segment] = $value;
        }
    }

    return $target;
}
