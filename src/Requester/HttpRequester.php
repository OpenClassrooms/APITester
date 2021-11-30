<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use OpenAPITesting\Requester;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class HttpRequester implements Requester
{
    public function request(ServerRequestInterface $request): ResponseInterface
    {
        return (new Psr18Client())->sendRequest($request);
    }
}
