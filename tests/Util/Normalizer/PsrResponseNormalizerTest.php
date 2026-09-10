<?php

declare(strict_types=1);

namespace APITester\Tests\Util\Normalizer;

use APITester\Util\Normalizer\PsrResponseNormalizer;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class PsrResponseNormalizerTest extends TestCase
{
    private PsrResponseNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new PsrResponseNormalizer();
    }

    public function testSupportsNormalizationForResponse(): void
    {
        static::assertTrue($this->normalizer->supportsNormalization(new Response()));
    }

    public function testSupportsNormalizationReturnsFalseForOther(): void
    {
        static::assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeExtractsFields(): void
    {
        $response = new Response(201, [
            'X-Custom' => 'val',
        ], 'plain body');

        $result = $this->normalizer->normalize($response);

        static::assertSame(201, $result['status']);
        static::assertSame('plain body', $result['body']);
        static::assertArrayHasKey('headers', $result);
    }

    public function testNormalizeDecodesJsonBody(): void
    {
        $body = '{"ok":true}';
        $response = new Response(200, [], $body);

        $result = $this->normalizer->normalize($response);

        static::assertSame([
            'ok' => true,
        ], $result['body']);
    }

    public function testNormalizeIgnoresAttributes(): void
    {
        $response = new Response(200, [], 'body');

        $result = $this->normalizer->normalize($response, null, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['headers'],
        ]);

        static::assertArrayNotHasKey('headers', $result);
        static::assertArrayHasKey('status', $result);
    }
}
