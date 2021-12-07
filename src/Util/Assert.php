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
     * @return array<string, array<int|string, string>>
     */
    public static function assertResponse(ResponseInterface $actual, ResponseInterface $expected): array
    {
        $errors = [];
        $errors = array_merge(
            $errors,
            self::assertStatusCode($actual->getStatusCode(), $expected->getStatusCode())
        );
        $errors = array_merge(
            $errors,
            self::assertHeaders($actual->getHeaders(), $expected->getHeaders())
        );
        $errors = array_merge(
            $errors,
            self::assertBody($actual->getBody(), $expected->getBody())
        );

        return $errors;
    }

    /**
     * @return array<string, array<string, string>>
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
     * @param array<int, array<int, string>> $actualHeaders
     * @param array<int, array<int, string>> $expectedHeaders
     *
     * @return array<string, array<int|string, string>>
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
     * @return array<string, array<string, string>>
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
