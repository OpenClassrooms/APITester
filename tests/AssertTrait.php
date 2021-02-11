<?php

namespace OpenAPITesting\Tests;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;

trait AssertTrait
{
    protected function assertRequests(array $expecteds, array $actuals)
    {
        Assert::assertCount(count($expecteds), $actuals);
        foreach ($expecteds as $key => $expected) {
            $this->assertRequest($expected, $actuals[$key]);
        }
    }

    protected function assertRequest(RequestInterface $expected, RequestInterface $actual)
    {
        Assert::assertEquals($expected->getUri(), $actual->getUri());
        Assert::assertEquals($expected->getMethod(), $actual->getMethod());
        Assert::assertEquals($expected->getHeaders(), $actual->getHeaders());
        Assert::assertEquals($expected->getBody()->getContents(), $actual->getBody()->getContents());
    }
}