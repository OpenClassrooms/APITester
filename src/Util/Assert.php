<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

use PHPUnit\Framework\Assert as BaseAssert;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class Assert
{
    /**
     * @param object|array<mixed> $expected
     * @param object|array<mixed> $actual
     * @param array<string>       $exclude
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public static function assertObjectsEqual(
        $expected,
        $actual,
        array $exclude = [],
        string $message = ''
    ): ?ExpectationFailedException {
        $serializer = self::getJsonSerializer();

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => static function (array $object): string {
                return Json::encode($object);
            },
        ];
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES] = $exclude;

        $json = [];
        foreach ([
            'expected' => $expected,
            'actual' => $actual,
        ] as $val) {
            if ($val instanceof \stdClass) {
                $val = Object_::toArray($val);
            }
            $json[] = $serializer->serialize(
                $val,
                'json',
                $context
            );
        }

        try {
            BaseAssert::assertJsonStringEqualsJsonString(
                $json[0],
                $json[1],
                $message
            );
        } catch (ExpectationFailedException $exception) {
            return $exception;
        }

        return null;
    }

    private static function getJsonSerializer(): Serializer
    {
        return new Serializer(
            [
                new JsonSerializableNormalizer(),
                new DateTimeZoneNormalizer(),
                new DateTimeNormalizer(),
                new DateIntervalNormalizer(),
                new GetSetMethodNormalizer(),
                new ObjectNormalizer(),
            ],
            [new JsonEncoder()]
        );
    }
}
