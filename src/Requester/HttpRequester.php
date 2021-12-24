<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use Nyholm\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class HttpRequester implements Requester
{
    private string $baseUri;

    public function __construct(string $baseUri = '')
    {
        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * @inheritDoc
     */
    public function request(RequestInterface $request): ResponseInterface
    {
        $request = $request->withUri(new Uri($this->baseUri . $request->getUri()));

        return (new Psr18Client())->sendRequest($request);
    }
}
