<?php

declare(strict_types=1);

namespace APITester\Tests\Test;

use APITester\Test\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function testSuccessFactory(): void
    {
        $result = Result::success('All good', 'OK');

        static::assertTrue($result->hasSucceeded());
        static::assertSame('success', $result->getStatus());
    }

    public function testSuccessDefaultMessage(): void
    {
        $result = Result::success();

        static::assertSame('Succeeded.', (string) $result);
    }

    public function testFailedFactory(): void
    {
        $result = Result::failed('Something broke', 'ERR');

        static::assertFalse($result->hasSucceeded());
        static::assertSame('failed', $result->getStatus());
    }

    public function testToStringWithCode(): void
    {
        $result = Result::failed('not found', '404');

        static::assertSame('404: not found', (string) $result);
    }

    public function testToStringWithoutCode(): void
    {
        $result = Result::failed('not found');

        static::assertSame('not found', (string) $result);
    }

    public function testJsonSerializeReturnsCorrectShape(): void
    {
        $result = Result::success('done', 'OK');
        $serialized = $result->jsonSerialize();

        static::assertSame('OK', $serialized['code']);
        static::assertSame('done', $serialized['message']);
        static::assertCount(2, $serialized);
    }
}
