<?php

namespace OpenAPITesting\Models\Test;

use InvalidArgumentException;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Webmozart\Assert\Assert;

trait AssertionTrait
{
    protected function assertResponse(ResponseInterface $actual, \Psr\Http\Message\ResponseInterface $expected)
    {
        $errors = [];
        $errors = array_merge($errors, $this->assertStatusCode($actual->getStatusCode(), $expected->getStatusCode()));
        $errors = array_merge($errors, $this->assertHeaders($actual->getHeaders(), $expected->getHeaders()));
        $errors = array_merge($errors, $this->assertBody($actual->getContent(), $expected->getBody()));

        return $errors;
    }

    private function assertStatusCode(int $actualStatusCode, int $expectedStatusCode): array
    {
        $errors = [];
        try {
            Assert::same($actualStatusCode, $expectedStatusCode);
        } catch (InvalidArgumentException $iae) {
            $errors['statusCode']['same'] = $iae->getMessage();
        }

        return $errors;
    }

    private function assertHeaders(array $actualHeaders, array $expectedHeaders): array
    {
        $errors = [];
        foreach ($expectedHeaders as $name => $expectedValue) {
            try {
                Assert::keyExists($actualHeaders, $name);
            } catch (InvalidArgumentException $iae) {
                $errors['headers'][$name] = $iae->getMessage();
                break;
            }
            try {
                Assert::same($actualHeaders[$name], $expectedValue);
            } catch (InvalidArgumentException $iae) {
                $errors['headers'][$name] = $iae->getMessage();
            }
        }

        return $errors;
    }

    private function assertBody(string $actualBody, string $expectedBody): array
    {
        $errors = [];
        try {
            Assert::same($actualBody, $expectedBody);
        } catch (InvalidArgumentException $iae) {
            $errors['body']['same'] = $iae->getMessage();
        }

        return $errors;
    }
}