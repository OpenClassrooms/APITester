<?php

declare(strict_types=1);

namespace OpenAPITesting\Requester;

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

    abstract public static function getName(): string;

    /**
     * @throws ClientExceptionInterface
     */
    abstract public function request(RequestInterface $request, string $id): void;

    abstract public function getResponse(string $id): ResponseInterface;

    abstract public function setBaseUri(string $baseUri): void;

    protected function fillRequestVars(RequestInterface $request): void
    {
        /** @var string $header */
        foreach ($request->getHeaders() as $name => $header) {
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
