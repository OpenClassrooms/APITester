<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

use DirectoryIterator;

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

    /**
     * @return array<array-key, mixed>
     */
    public static function concatFromDirectory(?string $path): array
    {
        if (null === $path) {
            return [];
        }

        $directory = __DIR__ . '/../../../' . $path;

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
}
