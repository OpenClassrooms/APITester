<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

use hanneskod\classtools\Iterator\ClassIterator;
use PhpCsFixer\Finder;

final class Object_
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(object $object): array
    {
        $array = [];
        $ref = new \ReflectionObject($object);
        foreach ($ref->getProperties() as $propRef) {
            $propRef->setAccessible(true);
            /** @var array<string, mixed> */
            $array[$propRef->getName()] = $propRef->getValue($object);
        }

        return $array;
    }

    /**
     * @template T
     *
     * @param class-string<T> $interface
     *
     * @return array<T>
     */
    public static function getImplementations(string $interface): array
    {
        $objects = [];
        $implementations = self::getSubTypesOf($interface)->where('isInstantiable');

        /** @var \ReflectionClass<T> $class */
        foreach ($implementations as $class) {
            try {
                if (!$class->isFinal()) {
                    continue;
                }
                $constructor = $class->getConstructor();
                if (null !== $constructor && $constructor->getNumberOfRequiredParameters() > 0) {
                    continue;
                }
                $objects[] = $class->newInstance();
            } catch (\ReflectionException $e) {
                continue;
            }
        }

        return $objects;
    }

    /**
     * @template T
     *
     * @param class-string<T> $interface
     *
     * @return ClassIterator<\ReflectionClass<T>>
     */
    public static function getSubTypesOf(string $interface): ClassIterator
    {
        $finder = new Finder();
        $iter = new ClassIterator($finder->in(PROJECT_DIR . '/src'));
        $iter->enableAutoloading();

        /** @var ClassIterator<\ReflectionClass<T>> $iter */
        return $iter->type($interface);
    }
}
