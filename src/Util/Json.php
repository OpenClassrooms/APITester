<?php

declare(strict_types=1);

namespace APITester\Util;

final class Json
{
    public static function isJson(string $string): bool
    {
        try {
            self::decode($string);

            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }

    /**
     * @param int<1, max> $depth
     *
     * @throws \JsonException
     *
     * @return array<int|string, mixed>
     */
    public static function decode(string $json, int $depth = 512): array
    {
        return (array) json_decode($json, true, $depth, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     */
    public static function prettify(string $json): string
    {
        return json_encode(self::decode($json), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * @param mixed[]|object $data
     */
    public static function serialize($data): string
    {
        if ($data instanceof \stdClass) {
            return self::encode($data);
        }

        return Serializer::create()
            ->serialize($data, 'json')
        ;
    }

    /**
     * @param mixed[]|object $data
     */
    public static function encode($data, int $flags = JSON_THROW_ON_ERROR): string
    {
        if (JSON_THROW_ON_ERROR !== $flags) {
            $flags |= JSON_THROW_ON_ERROR;
        }

        return (string) json_encode($data, $flags);
    }

    /**
     * @return array<mixed>|object
     */
    public static function deserialize(string $data, string $type)
    {
        return Serializer::create()
            ->deserialize($data, $type, 'json')
        ;
    }
}
