<?php

declare(strict_types=1);

namespace OpenAPITesting\Util;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Webmozart\Assert\Assert as BaseAssert;

class Assert extends BaseAssert
{
    /**
     * @param \Psr\Http\Message\ResponseInterface $actual
     * @param \Psr\Http\Message\ResponseInterface $expected
     *
     * @return array<string, array<int|string, string>>
     */
    public static function assertResponse(ResponseInterface $actual, ResponseInterface $expected): array
    {
        $errors = [];
        $errors = array_merge($errors, self::assertStatusCode($actual->getStatusCode(), $expected->getStatusCode()));
        $errors = array_merge($errors, self::assertHeaders($actual->getHeaders(), $expected->getHeaders()));
        $errors = array_merge($errors, self::assertBody($actual->getBody(), $expected->getBody()));

        return $errors;
    }

    /**
     * @param int $actualStatusCode
     * @param int $expectedStatusCode
     *
     * @return array<string, array<string, string>>
     */
    private static function assertStatusCode(int $actualStatusCode, int $expectedStatusCode): array
    {
        $errors = [];
        try {
            static::same($actualStatusCode, $expectedStatusCode);
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
                static::keyExists($actualHeaders, $name);
            } catch (InvalidArgumentException $iae) {
                $errors['headers'][$name] = $iae->getMessage();
                break;
            }
            try {
                static::same($actualHeaders[$name], $expectedValue);
            } catch (InvalidArgumentException $iae) {
                $errors['headers'][$name] = $iae->getMessage();
            }
        }

        return $errors;
    }

    /**
     * @param StreamInterface $actualBody
     * @param StreamInterface $expectedBody
     *
     * @return array<string, array<string, string>>
     */
    private static function assertBody(StreamInterface $actualBody, StreamInterface $expectedBody): array
    {
        $errors = [];
        try {
            static::same($actualBody, $expectedBody);
        } catch (InvalidArgumentException $iae) {
            $errors['body']['same'] = $iae->getMessage();
        }

        return $errors;
    }
}
