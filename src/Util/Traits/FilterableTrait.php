<?php

declare(strict_types=1);

namespace APITester\Util\Traits;

use Symfony\Component\PropertyAccess\PropertyAccess;

trait FilterableTrait
{
    public function has(string $prop, $value, string $operator = '='): bool
    {
        $retrieved = $this->getFilterValue($this, explode('.', $prop));

        if (str_contains($prop, '*')) {
            $operator = 'contains';
        }

        return match ($operator) {
            '=', '==', '===' => $retrieved === $value,
            '!=', '<>', '!==' => $retrieved !== $value,
            '<' => $retrieved < $value,
            '>' => $retrieved > $value,
            '<=' => $retrieved <= $value,
            '>=' => $retrieved >= $value,
            'contains' => (is_array($retrieved) && in_array($value, $retrieved, true))
                || (is_string($retrieved) && str_contains($retrieved, (string) $value)),
            'includes' => (is_array($retrieved) && collect((array) $value)->diff($retrieved)->isEmpty())
                || (is_string($retrieved) && str_contains($retrieved, (string) $value)),
            default => $retrieved === $value,
        };
    }

    /**
     * @param list<string> $segments
     */
    private function getFilterValue(mixed $target, array $segments): mixed
    {
        if ($segments === []) {
            return $target;
        }

        $segment = array_shift($segments);
        if ($segment === '*') {
            if (!is_iterable($target)) {
                return null;
            }
            $values = [];
            foreach ($target as $item) {
                $values[] = $this->getFilterValue($item, $segments);
            }

            return in_array('*', $segments, true) ? collect($values)
                ->collapse()
                ->all() : $values;
        }

        if (is_array($target) || $target instanceof \ArrayAccess) {
            $target = $target[$segment] ?? null;
        } elseif (is_object($target)) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $target = $accessor->isReadable($target, $segment) ? $accessor->getValue($target, $segment) : null;
        } else {
            return null;
        }

        return $this->getFilterValue($target, $segments);
    }
}
