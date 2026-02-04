<?php

declare(strict_types=1);

namespace APITester\Tests\Util\Normalizer;

use APITester\Util\Normalizer\PsrRequestNormalizer;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class PsrRequestNormalizerTest extends TestCase
{
    private PsrRequestNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new PsrRequestNormalizer();
    }

    public function testSupportsNormalizationForRequest(): void
    {
        static::assertTrue($this->normalizer->supportsNormalization(new Request('GET', '/')));
    }

    public function testSupportsNormalizationReturnsFalseForOther(): void
    {
        static::assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeExtractsFields(): void
    {
        $request = new Request('POST', 'https://example.com/api', [
            'Accept' => 'text/plain',
        ], 'raw body');

        $result = $this->normalizer->normalize($request);

        static::assertSame('POST', $result['method']);
        static::assertSame('https://example.com/api', $result['url']);
        static::assertSame('raw body', $result['body']);
        static::assertArrayHasKey('headers', $result);
    }

    public function testNormalizeDecodesJsonBody(): void
    {
        $body = '{"key":"value"}';
        $request = new Request('POST', '/api', [], $body);

        $result = $this->normalizer->normalize($request);

        static::assertSame([
            'key' => 'value',
        ], $result['body']);
    }

    public function testNormalizeIgnoresAttributes(): void
    {
        $request = new Request('GET', '/api');

        $result = $this->normalizer->normalize($request, null, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['headers', 'body'],
        ]);

        static::assertArrayNotHasKey('headers', $result);
        static::assertArrayNotHasKey('body', $result);
    }
}
