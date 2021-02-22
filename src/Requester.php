<?php

declare(strict_types=1);

namespace OpenAPITesting;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Requester
{
    public function request(ServerRequestInterface $request): ResponseInterface;
}
