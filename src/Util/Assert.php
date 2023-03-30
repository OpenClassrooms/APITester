<?php

declare(strict_types=1);

namespace APITester\Util;

use APITester\Definition\Example\ResponseExample;
use APITester\Util\Normalizer\PsrRequestNormalizer;
use APITester\Util\Normalizer\PsrResponseNormalizer;
use PHPUnit\Framework\Assert as BaseAssert;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
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
    private static PropertyAccessorInterface $accessor;

    /**
     * @param object|iterable<mixed> $expected
     * @param object|iterable<mixed> $actual
     * @param array<string>          $exclude
     *
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public static function objectsEqual(
        iterable|object $expected,
        iterable|object $actual,
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
     * @template T
     *
     * @param T $expected
     * @param T $actual
     */
    public static function same($expected, $actual, string $message = ''): void
    {
        BaseAssert::assertSame($expected, $actual, $message);
    }

    /**
     * @throws ExpectationFailedException
     */
    public static function true(mixed $actual, string $message = ''): void
    {
        BaseAssert::assertTrue($actual, $message);
    }

    /**
     * @param array<string> $excludedFields
     *
     * @throws ExceptionInterface
     */
    public static function response(
        ResponseExample $expected,
        ResponseExample $actual,
        array $excludedFields = []
    ): void {
        $serialize = self::getJsonSerializer();

        foreach ($excludedFields as &$excludedField) {
            $excludedField = str_replace('body', 'content', $excludedField);
        }

        /** @var array<string, mixed> $expected */
        $expected = $serialize->normalize($expected, null, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => $excludedFields,
        ]);
        /** @var array<string, mixed> $actual */
        $actual = $serialize->normalize($actual, null, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => $excludedFields,
        ]);

        self::initAccessor();
        $paths = self::getPaths($expected, $excludedFields);

        $expected = Json::decodeAsObject(Json::encode($expected));
        $actual = Json::decodeAsObject(Json::encode($actual));

        foreach ($paths as $path) {
            $path = (string) preg_replace('/\.(\d)/', '[$1]', $path);
            $expectedValue = self::$accessor->getValue($expected, $path);
            $actualValue = self::$accessor->getValue($actual, $path);
            $message = "Checking {$path}";
            if (str_starts_with((string) $expectedValue, '#') && str_ends_with((string) $expectedValue, '#')) {
                BaseAssert::assertMatchesRegularExpression(
                    \is_array($expectedValue) ? Json::encode($expectedValue) : (string) $expectedValue,
                    \is_array($actualValue) ? Json::encode($actualValue) : (string) $actualValue,
                    $message,
                );
            } else {
                BaseAssert::assertEquals(
                    $expectedValue,
                    $actualValue,
                    $message,
                );
            }
        }
    }

    private static function getJsonSerializer(): Serializer
    {
        return new Serializer(
            [
                new JsonSerializableNormalizer(),
                new DateTimeZoneNormalizer(),
                new DateTimeNormalizer(),
                new DateIntervalNormalizer(),
                new PsrRequestNormalizer(),
                new PsrResponseNormalizer(),
                new PropertyNormalizer(),
                new ObjectNormalizer(),
            ],
            [new JsonEncoder()]
        );
    }

    private static function initAccessor(): void
    {
        self::$accessor = (new PropertyAccessorBuilder())->getPropertyAccessor();
    }

    /**
     * @param array<string, mixed> $array
     * @param array<string>        $excludedFields
     *
     * @return array<string>
     */
    private static function getPaths(array $array, array $excludedFields = [], string $prefix = null): array
    {
        self::initAccessor();
        $paths = [];
        foreach ($array as $key => $value) {
            $path = $prefix === null ? $key : $prefix . '.' . $key;
            if (\in_array($path, $excludedFields, true)) {
                continue;
            }
            if (\is_array($value)) {
                /** @var array<string, mixed> $value */
                $paths = array_merge(
                    $paths,
                    self::getPaths(
                        $value,
                        $excludedFields,
                        $path
                    )
                );
            } else {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
