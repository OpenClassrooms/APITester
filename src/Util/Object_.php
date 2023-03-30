<?php

declare(strict_types=1);

namespace APITester\Util;

use hanneskod\classtools\Iterator\ClassIterator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

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
     * @param array<string, mixed> $data
     * @param class-string<T>      $type
     *
     * @throws ExceptionInterface
     *
     * @return T
     */
    public static function fromArray(array $data, string $type)
    {
        return Serializer::create()
            ->denormalize($data, $type)
        ;
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
                if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                    continue;
                }
                $objects[] = $class->newInstance();
            } catch (\ReflectionException) {
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

    /**
     * @template T
     *
     * @param class-string<T> $type
     *
     * @return class-string<T>
     */
    public static function validateClass(string $class, string $type): string
    {
        if (!class_exists($class) || !is_a($class, $type, true)) {
            throw new \RuntimeException("Invalid class {$class} for type {$type}.");
        }

        /** @var class-string<T> $class */
        return $class;
    }

    /**
     * @template T
     *
     * @param class-string<T> $interface
     *
     * @return array<class-string<T>>
     */
    public static function getImplementationsClasses(string $interface): array
    {
        $classes = [];
        $implementations = self::getSubTypesOf($interface)
            ->where('isInstantiable')
        ;

        /** @var \ReflectionClass<T> $class */
        foreach ($implementations as $class) {
            if (!$class->isFinal()) {
                continue;
            }
            $classes[] = $class->getName();
        }

        return $classes;
    }
}
