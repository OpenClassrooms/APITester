<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class Json
{
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
     * @param mixed[]|object $data
     */
    public static function encode($data, int $flags = JSON_THROW_ON_ERROR): string
    {
        if (JSON_THROW_ON_ERROR !== $flags) {
            $flags |= JSON_THROW_ON_ERROR;
        }

        return (string) json_encode($data, $flags);
    }

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

        return self::getJsonSerializer()
            ->serialize($data, 'json')
        ;
    }

    private static function getJsonSerializer(): Serializer
    {
        return new Serializer(
            [
                new JsonSerializableNormalizer(),
                new DateTimeZoneNormalizer(),
                new DateTimeNormalizer(),
                new DateIntervalNormalizer(),
                new ObjectNormalizer(),
            ],
            [new JsonEncoder()]
        );
    }
}