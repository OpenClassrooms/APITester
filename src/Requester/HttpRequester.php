<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use OpenAPITesting\Requester;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;

class HttpRequester implements Requester
{
    public function request(RequestInterface $request): ResponseInterface
    {
        return (new Psr18Client())->sendRequest($request);
    }
}
