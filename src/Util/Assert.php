<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

use OpenAPITesting\Util\Normalizer\StreamNormalizer;
use PHPUnit\Framework\Assert as BaseAssert;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

final class Assert
{
    /**
     * @param iterable<mixed>|object $expected
     * @param iterable<mixed>|object $actual
     * @param array<string>          $exclude
     *
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public static function objectsEqual(
        $expected,
        $actual,
        array $exclude = [],
        string $message = ''
    ): void {
        $serializer = self::getJsonSerializer();

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => static fn ($it): string => Json::encode($it),
            AbstractNormalizer::IGNORED_ATTRIBUTES => $exclude,
        ];

        $json = [];
        foreach (
            [
                'expected' => $expected,
                'actual' => $actual,
            ] as $val
        ) {
            if ($val instanceof \stdClass) {
                $val = Object_::toArray($val);
            }
            $json[] = $serializer->serialize(
                $val,
                'json',
                $context
            );
        }

        BaseAssert::assertJsonStringEqualsJsonString(
            $json[0],
            $json[1],
            $message
        );
    }

    /**
     * @param mixed $actual
     *
     * @throws ExpectationFailedException
     */
    public static function true($actual, string $message = ''): void
    {
        BaseAssert::assertTrue($actual, $message);
    }

    private static function getJsonSerializer(): Serializer
    {
        return new Serializer(
            [
                new JsonSerializableNormalizer(),
                new DateTimeZoneNormalizer(),
                new DateTimeNormalizer(),
                new DateIntervalNormalizer(),
                new StreamNormalizer(),
                new PropertyNormalizer(),
                new ObjectNormalizer(),
            ],
            [new JsonEncoder()]
        );
    }
}
