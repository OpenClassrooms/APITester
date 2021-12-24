<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Requester
{
    /**
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function request(RequestInterface $request): ResponseInterface;
}
