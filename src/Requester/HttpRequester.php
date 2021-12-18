<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use Nyholm\Psr7\Uri;
use OpenAPITesting\Requester;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class HttpRequester implements Requester
{
    private string $baseUri;

    public function __construct(string $baseUri = '')
    {
        $this->baseUri = $baseUri;
    }

    /**
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function request(RequestInterface $request): ResponseInterface
    {
        $request = $request->withUri(new Uri($this->baseUri . $request->getUri()));

        return (new Psr18Client())->sendRequest($request);
    }
}
