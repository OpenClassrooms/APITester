<?php

declare(strict_types=1);

namespace APITester\Tests\Requester;

use APITester\Requester\HttpDumpRequester;
use APITester\Requester\Requester;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;

final class RequesterTest extends TestCase
{
    public function testSetBaseUriTrimsTrailingSlashes(): void
    {
        $requester = new HttpDumpRequester();
        $requester->setBaseUri('https://example.com/api/');

        static::assertSame('https://example.com/api', $requester->getBaseUri());
    }

    public function testSetBaseUriTrimsMultipleTrailingSlashes(): void
    {
        $requester = new HttpDumpRequester();
        $requester->setBaseUri('https://example.com///');

        static::assertSame('https://example.com', $requester->getBaseUri());
    }

    public function testResolveUriPrependsBaseUriForRelativePath(): void
    {
        $requester = new HttpDumpRequester();
        $requester->setBaseUri('https://example.com/api');

        $request = new Request('GET', '/users/1');
        $resolved = $requester->resolveUri($request);

        static::assertSame('https://example.com/api/users/1', (string) $resolved->getUri());
    }

    public function testResolveUriDoesNotPrependForAbsoluteUri(): void
    {
        $requester = new HttpDumpRequester();
        $requester->setBaseUri('https://example.com/api');

        $request = new Request('GET', 'https://other.com/resource');
        $resolved = $requester->resolveUri($request);

        static::assertSame('https://other.com/resource', (string) $resolved->getUri());
    }

    public function testResolveUriWithEmptyBaseUriReturnsUnchanged(): void
    {
        $requester = new HttpDumpRequester();

        $request = new Request('GET', '/users');
        $resolved = $requester->resolveUri($request);

        static::assertSame('/users', (string) $resolved->getUri());
    }

    /**
     * @dataProvider fillVarsProvider
     */
    public function testFillVarsReplacesPlaceholders(string $subject, string $expected): void
    {
        $requester = new HttpDumpRequester();

        $varsRef = new \ReflectionProperty(Requester::class, 'vars');
        $varsRef->setAccessible(true);
        $varsRef->setValue($requester, [
            'name' => 'John',
            'id' => '42',
        ]);

        $fillVarsRef = new \ReflectionMethod(Requester::class, 'fillVars');
        $fillVarsRef->setAccessible(true);

        $result = $fillVarsRef->invoke($requester, $subject);

        static::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public function fillVarsProvider(): iterable
    {
        yield 'single placeholder' => ['/users/{id}', '/users/42'];
        yield 'multiple placeholders' => ['{name} has id {id}', 'John has id 42'];
        yield 'no placeholders' => ['plain text', 'plain text'];
    }
}
