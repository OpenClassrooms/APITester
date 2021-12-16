<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

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
     * @param array<mixed, mixed> $data
     */
    public static function encode(array $data, int $flags = JSON_THROW_ON_ERROR): string
    {
        if (JSON_THROW_ON_ERROR !== $flags) {
            $flags |= JSON_THROW_ON_ERROR;
        }

        return (string) json_encode($data, $flags);
    }
}
