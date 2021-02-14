<?php

declare(strict_types=1);

namespace OpenAPITesting;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Requester
{
    public function request(RequestInterface $request): ResponseInterface;
}
