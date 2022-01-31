<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class SymfonyKernelRequester extends Requester
{
    private KernelInterface $kernel;

    /**
     * @var ResponseInterface[]
     */
    private array $responses = [];

    private string $baseUri;

    public function __construct(KernelInterface $kernel, string $baseUri = '')
    {
        $this->kernel = $kernel;
        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * @inheritDoc
     */
    public function request(RequestInterface $request, string $id): void
    {
        $request = $request->withUri(new Uri($this->baseUri . $request->getUri()));
        try {
            $this->responses[$id] = $this->symfonyToPsrResponse(
                $this->kernel->handle($this->psrToSymfonyRequest($request))
            );
        } catch (\Exception $e) {
            throw new ClientException(new MockResponse((string) $e));
        }
    }

    public function getResponse(string $id): ResponseInterface
    {
        return $this->responses[$id];
    }

    public static function getName(): string
    {
        return 'symfony-kernel';
    }

    public function setBaseUri(string $baseUri): void
    {
        $this->baseUri = $baseUri;
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
