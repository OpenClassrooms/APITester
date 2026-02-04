<?php

declare(strict_types=1);

namespace APITester\Requester;

use Nyholm\Psr7\Stream;
use Nyholm\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class Requester
{
    /**
     * @var array<string, string>
     */
    private array $vars = [];

    private string $baseUri = '';

    abstract public static function getName(): string;

    /**
     * @throws ClientExceptionInterface
     */
    abstract public function request(RequestInterface $request, string $id): RequestInterface;

    abstract public function getResponse(string $id): ResponseInterface;

    final public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    final public function setBaseUri(string $baseUri): void
    {
        $this->baseUri = rtrim($baseUri, '/');
    }

    final public function resolveUri(RequestInterface $request): RequestInterface
    {
        if ($this->baseUri !== '' && !str_starts_with((string) $request->getUri(), 'http')) {
            return $request->withUri(new Uri($this->baseUri . $request->getUri()));
        }

        return $request;
    }

    protected function fillRequestVars(RequestInterface $request): void
    {
        foreach ($request->getHeaders() as $name => $header) {
            $header = implode(', ', $header);
            $header = $this->fillVars($header);
            $request->withHeader($name, $header);
        }
        $request->withUri(new Uri($this->fillVars((string) $request->getUri())));
        $request->withBody(Stream::create($this->fillVars((string) $request->getBody())));
    }

    protected function fillVars(string $subject): string
    {
        return str_replace(
            array_map(
                static fn (string $x) => "{{$x}}",
                array_keys($this->vars)
            ),
            $this->vars,
            $subject
        );
    }
}
