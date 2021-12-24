<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class SymfonyKernelRequester implements Requester
{
    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @inheritDoc
     */
    public function request(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->symfonyToPsrResponse(
                $this->kernel->handle($this->psrToSymfonyRequest($request))
            );
        } catch (\Exception $e) {
            throw new ClientException(new MockResponse((string) $e));
        }
    }

    private function symfonyToPsrResponse(Response $symfonyResponse): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        return $psrHttpFactory->createResponse($symfonyResponse);
    }

    private function psrToSymfonyRequest(RequestInterface $request): Request
    {
        $serverRequest = new ServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody(),
        );

        return (new HttpFoundationFactory())->createRequest($serverRequest);
    }
}
