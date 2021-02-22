<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use OpenAPITesting\Requester;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpClient\Psr18Client;

class HttpRequester implements Requester
{
    /**
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function request(ServerRequestInterface $request): ResponseInterface
    {
        return (new Psr18Client())->sendRequest($request);
    }
}
