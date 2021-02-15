<?php

namespace OpenAPITesting\Tests\Fixtures;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientMock implements ClientInterface
{
    public static array $requests = [];

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        self::$requests[] = $request;

        return new Response();
    }
}