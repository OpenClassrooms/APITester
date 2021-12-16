<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use OpenAPITesting\Requester;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class HttpRequester implements Requester
{
    /**
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function request(RequestInterface $request): ResponseInterface
    {
        return (new Psr18Client())->sendRequest($request);
    }
}
