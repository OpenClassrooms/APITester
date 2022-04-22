<?php

declare(strict_types=1);

namespace APITester\Util;

use DirectoryIterator;

final class Yaml
{
    /**
     * @return array<array-key, mixed>
     */
    public static function parseFile(?string $path): array
    {
        if (null === $path) {
            return [];
        }

        /** @var array<array-key, mixed> */
        return \Symfony\Component\Yaml\Yaml::parseFile($path);
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function concatFromDirectory(?string $path): array
    {
        if (null === $path) {
            return [];
        }

        $directory = \dirname(__DIR__, 2) . '/' . trim($path, '/');

        $data = [];
        /** @var DirectoryIterator $fileInfo */
        foreach (new DirectoryIterator($directory) as $fileInfo) {
            if (\in_array($fileInfo->getFilename(), ['.', '..'], true)) {
                continue;
            }

            $yaml = file_get_contents($fileInfo->getPath() . '/' . $fileInfo->getFilename());
            if (false === $yaml) {
                continue;
            }

            $data[] = (array) \Symfony\Component\Yaml\Yaml::parse($yaml);
        }

        return array_merge(...$data);
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

    /**
     * @param mixed[]|object $data
     */
    public static function serialize($data): string
    {
        return Serializer::create()
            ->serialize($data, 'yaml')
        ;
    }
}
