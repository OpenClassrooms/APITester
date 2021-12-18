<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

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
}
