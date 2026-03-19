<?php

declare(strict_types=1);

namespace APITester\Requester;

use APITester\Util\Json;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SymfonyKernelRequester extends Requester
{
    private HttpKernelInterface $kernel;

    /**
     * @var ResponseInterface[]
     */
    private array $responses = [];

    public function __construct(string $baseUri = '')
    {
        $this->setBaseUri($baseUri);
    }

    /**
     * @inheritDoc
     */
    public function request(RequestInterface $request, string $id): RequestInterface
    {
        $request = $this->resolveUri($request);
        try {
            $sfRequest = $this->psrToSymfonyRequest($request);
            $response = $this->kernel->handle($sfRequest);
            $this->responses[$id] = $this->symfonyToPsrResponse($response);
        } catch (\Throwable $e) {
            $response = new Response(Json::encode($e), 500);
            $this->responses[$id] = $this->symfonyToPsrResponse($response);
        }

        return $request;
    }

    public function getResponse(string $id): ResponseInterface
    {
        return $this->responses[$id];
    }

    public static function getName(): string
    {
        return 'symfony-kernel';
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

        parse_str($request->getUri()->getQuery(), $queryParams);
        $serverRequest = $serverRequest->withQueryParams($queryParams);

        return (new HttpFoundationFactory())->createRequest($serverRequest);
    }

    private function symfonyToPsrResponse(Response $symfonyResponse): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        return $psrHttpFactory->createResponse($symfonyResponse);
    }
}
