<?php

declare(strict_types=1);

namespace APITester\Tests\Util;

use APITester\Util\Json;
use PHPUnit\Framework\TestCase;

final class JsonTest extends TestCase
{
    /**
     * @dataProvider isJsonProvider
     */
    public function testIsJson(string $input, bool $expected): void
    {
        static::assertSame($expected, Json::isJson($input));
    }

    /**
     * @return iterable<string, array{0: string, 1: bool}>
     */
    public static function isJsonProvider(): iterable
    {
        yield 'valid object' => ['{"key":"value"}', true];
        yield 'valid array' => ['[1,2,3]', true];
        yield 'invalid string' => ['not json', false];
        yield 'empty string' => ['', false];
    }

    public function testDecodeReturnsAssociativeArray(): void
    {
        $result = Json::decode('{"foo":"bar","num":42}');

        static::assertSame([
            'foo' => 'bar',
            'num' => 42,
        ], $result);
    }

    public function testDecodeThrowsOnInvalidInput(): void
    {
        $this->expectException(\JsonException::class);

        Json::decode('invalid');
    }

    public function testDecodeAsObjectReturnsObject(): void
    {
        $result = Json::decodeAsObject('{"foo":"bar"}');

        static::assertInstanceOf(\stdClass::class, $result);
        static::assertSame('bar', $result->foo);
    }

    public function testPrettifyReformatsCompactJson(): void
    {
        $compact = '{"a":1,"b":2}';
        $pretty = Json::prettify($compact);

        static::assertStringContainsString("\n", $pretty);
        static::assertSame(json_encode([
            'a' => 1,
            'b' => 2,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), $pretty);
    }

    public function testEncodeIncludesThrowOnError(): void
    {
        $result = Json::encode([
            'key' => 'value',
        ]);

        static::assertSame('{"key":"value"}', $result);
    }

    public function testEncodeMergesExtraFlags(): void
    {
        $result = Json::encode([
            'a' => 1,
        ], JSON_PRETTY_PRINT);

        static::assertStringContainsString("\n", $result);
    }
}
