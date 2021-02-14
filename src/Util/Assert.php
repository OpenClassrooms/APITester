<?php

namespace OpenAPITesting\Util;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert as BaseAssert;

class Assert extends BaseAssert
{
    public static function assertResponse(ResponseInterface $actual, ResponseInterface $expected): array
    {
        $errors = [];
        $errors = array_merge($errors, static::assertStatusCode($actual->getStatusCode(), $expected->getStatusCode()));
        $errors = array_merge($errors, static::assertHeaders($actual->getHeaders(), $expected->getHeaders()));
        $errors = array_merge($errors, static::assertBody($actual->getBody(), $expected->getBody()));

        return $errors;
    }

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

    private static function assertBody(string $actualBody, string $expectedBody): array
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
