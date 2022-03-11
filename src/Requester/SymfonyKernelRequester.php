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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class SymfonyKernelRequester extends Requester
{
    private string $baseUri;

    private KernelInterface $kernel;

    /**
     * @var ResponseInterface[]
     */
    private array $responses = [];

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
        if (!str_starts_with((string) $request->getUri(), 'http')) {
            $request = $request->withUri(new Uri(trim($this->baseUri . $request->getUri())));
        }
        try {
            $request = $this->psrToSymfonyRequest($request);
            $response = $this->kernel->handle($request);
            $this->responses[$id] = $this->symfonyToPsrResponse($response);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error while executing kernel request', 0, $e);
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

    private function psrToSymfonyRequest(RequestInterface $request): Request
    {
        $serverRequest = new ServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody(),
            '1.1',
            [
                'HTTPS' => 'on',
            ]
        );

        return (new HttpFoundationFactory())->createRequest($serverRequest);
    }

    private function symfonyToPsrResponse(Response $symfonyResponse): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        return $psrHttpFactory->createResponse($symfonyResponse);
    }
}
