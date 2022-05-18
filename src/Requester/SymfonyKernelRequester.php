<?php

declare(strict_types=1);

namespace APITester\Requester;

use APITester\Util\Json;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SymfonyKernelRequester extends Requester
{
    private string $baseUri;

    private HttpKernelInterface $kernel;

    /**
     * @var ResponseInterface[]
     */
    private array $responses = [];

    public function __construct(string $baseUri = '')
    {
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
//            $this->kernel->terminate($request, $response);
            $this->responses[$id] = $this->symfonyToPsrResponse($response);
        } catch (\Throwable $e) {
//            print_r($e->getTrace()[0]);
//            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
            $response = new Response(Json::encode($e), 500);
            $this->responses[$id] = $this->symfonyToPsrResponse($response);
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

    public function setKernel(HttpKernelInterface $kernel): void
    {
        $this->kernel = $kernel;
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
