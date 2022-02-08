<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

final class Yaml
{
    /**
     * @param mixed[]|object $data
     */
    public static function serialize($data): string
    {
        return Serializer::create()
            ->serialize($data, 'yaml')
        ;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T
     */
    public static function deserialize(string $data, string $type): object
    {
        return Serializer::create()
            ->deserialize($data, $type, 'yaml')
        ;
    }
}
