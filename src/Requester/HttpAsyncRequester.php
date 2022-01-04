<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

use Nyholm\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\HttplugClient;

final class HttpAsyncRequester implements Requester
{
    private string $baseUri;

    /**
     * @var ResponseInterface[]
     */
    private array $responses = [];

    /**
     * @var RequestInterface[]
     */
    private array $requests = [];

    private bool $launched = false;

    public function __construct(string $baseUri = '')
    {
        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * @inheritDoc
     */
    public function request(RequestInterface $request, string $id): void
    {
        $this->requests[$id] = $request;
    }

    public function getResponse(string $id): ResponseInterface
    {
        if (false === $this->launched) {
            $this->call();
        }

        return $this->responses[$id];
    }

    public static function getName(): string
    {
        return 'http-async';
    }

    public function setBaseUri(string $baseUri): void
    {
        $this->baseUri = $baseUri;
    }

    private function call(): void
    {
        $httpClient = new HttplugClient();
        foreach ($this->requests as $id => $request) {
            $request = $request->withUri(new Uri($this->baseUri . $request->getUri()));
            try {
                $httpClient
                    ->sendAsyncRequest($request)
                    ->then(
                        function (ResponseInterface $response) use ($id) {
                            $this->responses[$id] = $response;
                        },
                        function (\Throwable $exception) {
                            throw $exception;
                        }
                    )
                ;
            } catch (\Exception $e) {
                throw new \RuntimeException($e->getMessage(), \is_int($e->getCode()) ? $e->getCode() : 0, $e);
            }
        }
        $httpClient->wait();
        $this->launched = true;
    }
}