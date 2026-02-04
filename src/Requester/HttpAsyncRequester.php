<?php

declare(strict_types=1);

namespace APITester\Requester;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\HttplugClient;

final class HttpAsyncRequester extends Requester
{
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
        $this->setBaseUri($baseUri);
    }

    public static function getName(): string
    {
        return 'http-async';
    }

    /**
     * @inheritDoc
     */
    public function request(RequestInterface $request, string $id): RequestInterface
    {
        $request = $this->resolveUri($request);
        $this->launched = false;
        $this->requests[$id] = $request;

        return $request;
    }

    public function getResponse(string $id): ResponseInterface
    {
        if ($this->launched === false) {
            $this->call();
            unset($this->requests[$id]);
        }

        return $this->responses[$id];
    }

    private function call(): void
    {
        $httpClient = new HttplugClient();
        foreach ($this->requests as $id => $request) {
            try {
                $httpClient
                    ->sendAsyncRequest($request)
                    ->then(
                        function (ResponseInterface $response) use ($id) {
                            $this->responses[$id] = $response;
                        },
                        static function (\Throwable $exception): never {
                            echo "Error: {$exception->getMessage()}\n";
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
