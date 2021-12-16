<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Webmozart\Assert\Assert as BaseAssert;

final class Assert
{
    /**
     * @return array<array-key, array<array-key, string>>
     */
    public static function assertResponse(ResponseInterface $actual, ResponseInterface $expected): array
    {
        $errors = self::assertStatusCode(
            $actual->getStatusCode(),
            $expected->getStatusCode(),
        );
        $errors = array_merge(
            $errors,
            self::assertHeaders(
                $actual->getHeaders(),
                $expected->getHeaders(),
            )
        );

        return array_merge(
            $errors,
            self::assertBody(
                $actual->getBody(),
                $expected->getBody()
            )
        );
    }

    /**
     * @return string[][]
     *
     * @psalm-return array{statusCode?: array{same: string}}
     */
    private static function assertStatusCode(int $actualStatusCode, int $expectedStatusCode): array
    {
        $errors = [];
        try {
            BaseAssert::same($actualStatusCode, $expectedStatusCode);
        } catch (InvalidArgumentException $iae) {
            $errors['statusCode']['same'] = $iae->getMessage();
        }

        return $errors;
    }

    /**
     * @param array<array-key, array<array-key, string>> $actualHeaders
     * @param array<array-key, array<array-key, string>> $expectedHeaders
     *
     * @return string[][]
     *
     * @psalm-return array{headers?: array<string>}
     */
    private static function assertHeaders(array $actualHeaders, array $expectedHeaders): array
    {
        $errors = [];
        foreach ($expectedHeaders as $name => $expectedValue) {
            try {
                BaseAssert::keyExists($actualHeaders, $name);
            } catch (InvalidArgumentException $iae) {
                $errors['headers'][$name] = $iae->getMessage();
                break;
            }
            try {
                BaseAssert::same($actualHeaders[$name], $expectedValue);
            } catch (InvalidArgumentException $iae) {
                $errors['headers'][$name] = $iae->getMessage();
            }
        }

        return $errors;
    }

    /**
     * @return string[][]
     *
     * @psalm-return array{body?: array{same: string}}
     */
    private static function assertBody(StreamInterface $actualBody, StreamInterface $expectedBody): array
    {
        $errors = [];
        try {
            BaseAssert::same($actualBody, $expectedBody);
        } catch (InvalidArgumentException $iae) {
            $errors['body']['same'] = $iae->getMessage();
        }

        return $errors;
    }
}
