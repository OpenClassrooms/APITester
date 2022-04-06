<?php

declare(strict_types=1);

namespace APITester\Util;

use APITester\Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use APITester\Util\Normalizer\PsrRequestNormalizer;
use APITester\Util\Normalizer\PsrResponseNormalizer;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SfSerializer;
use Symfony\Component\Yaml\Yaml as SfYaml;

final class Serializer
{
    /**
     * @template T of object
     *
     * @param mixed[]         $data
     * @param class-string<T> $type
     *
     * @throws ExceptionInterface
     *
     * @return T
     */
    public static function denormalize(array $data, string $type)
    {
        return self::create()
            ->denormalize($data, $type)
        ;
    }

    public static function create(): SfSerializer
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        $phpStanExtractor = new PhpStanExtractor();

        $listExtractors = [$reflectionExtractor];
        $typeExtractors = [$phpStanExtractor, $phpDocExtractor, $reflectionExtractor];
        $descriptionExtractors = [$phpDocExtractor];
        $accessExtractors = [$reflectionExtractor];
        $propertyInitializableExtractors = [$reflectionExtractor];
        $propertyInfo = new PropertyInfoExtractor(
            $listExtractors,
            $typeExtractors,
            $descriptionExtractors,
            $accessExtractors,
            $propertyInitializableExtractors
        );

        return new SfSerializer(
            [
                new JsonSerializableNormalizer(),
                new DateTimeZoneNormalizer(),
                new DateTimeNormalizer(),
                new DateIntervalNormalizer(),
                new ArrayDenormalizer(),
                new PsrRequestNormalizer(),
                new PsrResponseNormalizer(),
                new ObjectNormalizer(
                    null,
                    null,
                    null,
                    $propertyInfo,
                ),
            ],
            [
                new YamlEncoder(null, null, [
                    'yaml_flags' => SfYaml::PARSE_CUSTOM_TAGS,
                ]),
                new JsonEncoder(),
                new XmlEncoder(),
                new CsvEncoder(),
            ]
        );
    }

    /**
     * @return mixed
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public static function normalize(object $object)
    {
        return self::create()
            ->normalize($object)
        ;
    }
}
