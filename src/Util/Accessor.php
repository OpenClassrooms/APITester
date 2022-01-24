<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Accessor
{
    /**
     * @param array|object $target
     * @param mixed        $default
     *
     * @return mixed
     */
    public static function get($target, string $key, $default = null)
    {
        $propertyAccessor = self::initPropertyAccessor();
        try {
            return $propertyAccessor->getValue($target, $key);
        } catch (AccessException $e) {
            return $default;
        }
    }

    /**
     * @param array|object $target
     * @param mixed        $value
     */
    public static function set(&$target, string $key, $value): void
    {
        $propertyAccessor = self::initPropertyAccessor();

        $propertyAccessor->setValue($target, $key, $value);
    }

    private static function initPropertyAccessor()
    {
        return PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->enableExceptionOnInvalidPropertyPath()
            ->enableMagicCall()
            ->getPropertyAccessor()
        ;
    }
}
