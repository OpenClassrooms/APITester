<?php

declare(strict_types=1);

namespace APITester\Util;

use APITester\Util\Normalizer\StreamNormalizer;
use PHPUnit\Framework\Assert as BaseAssert;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Message\ResponseInterface;
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

    /**
     * @param array<string> $excludedFields
     */
    public static function response(
        ResponseInterface $expected,
        ResponseInterface $actual,
        array $excludedFields = []
    ): void {
        $serialize = self::getJsonSerializer();
        try {
            /** @var array<string, mixed> $expected */
            $expected = $serialize->normalize($expected);
            /** @var array<string, mixed> $actual */
            $actual = $serialize->normalize($actual);
        } catch (ExceptionInterface $e) {
            throw new \RuntimeException('Failed to normalize response', 0, $e);
        }

        $excludedFields = array_map(
            static fn ($v) => 'body' === $v ? 'stream' : $v,
            $excludedFields
        );
        self::initAccessor();
        $paths = self::getPaths($expected);
        $paths = array_diff($paths, $excludedFields);
        foreach ($paths as $path) {
            $expectedValue = self::$accessor->getValue((object) $expected, $path);
            $actualValue = self::$accessor->getValue((object) $actual, $path);
            $label = str_replace('stream', 'body', $path);
            $message = "Checking {$label}";
            if (str_starts_with((string) $expectedValue, '#') && str_ends_with((string) $expectedValue, '#')) {
                BaseAssert::assertMatchesRegularExpression(
                    (string) $expectedValue,
                    (string) $actualValue,
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
                new StreamNormalizer(),
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
     *
     * @return array<string>
     */
    private static function getPaths(array $array, string $prefix = null): array
    {
        self::initAccessor();
        $paths = [];
        foreach ($array as $key => $value) {
            $path = null === $prefix ? $key : $prefix . '.' . $key;
            if (\is_array($value)) {
                $paths = array_merge($paths, self::getPaths($value, $path));
            } else {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
