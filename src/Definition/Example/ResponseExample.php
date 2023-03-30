<?php

declare(strict_types=1);

namespace APITester\Definition\Example;

use APITester\Util\Json;
use Psr\Http\Message\ResponseInterface;

final class ResponseExample
{
    private OperationExample $parent;

    private string $mediaType = 'application/json';

    private string $statusCode = '200';

    /**
     * @var array<string, string|int|int[]|string[]>
     */
    private array $headers = [];

    public function __construct(
        ?string $statusCode = null,
        private mixed $content = null
    ) {
        $this->statusCode = $statusCode ?? $this->statusCode;
    }

    public static function create(?string $statusCode = null, mixed $content = null): self
    {
        return new self($statusCode, $content);
    }

    public static function fromPsrResponse(ResponseInterface $response): self
    {
        $example = new self();
        $example->setStatusCode((string) $response->getStatusCode());
        $example->setHeaders($response->getHeaders());
        $example->setContent($response->getBody()->getContents());

        return $example;
    }

    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    public function setStatusCode(string $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return array<string, string|int|int[]|string[]>
     */
    public function getHeaders(): array
    {
        if ($this->content !== null && !isset($this->headers['content-type'])) {
            $this->headers['content-type'] = $this->getMediaType();
        }

        return $this->headers;
    }

    /**
     * @param array<string, string|int|int[]|string[]> $headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    public function setContent(mixed $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getStringContent(): string
    {
        if ($this->content === null) {
            return '';
        }
        if (is_scalar($this->content)) {
            return (string) $this->content;
        }
        if (\is_object($this->content) || \is_array($this->content)) {
            return Json::encode($this->content);
        }

        throw new \RuntimeException('Invalid response example content type');
    }

    public function getParent(): OperationExample
    {
        return $this->parent;
    }

    public function setParent(OperationExample $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
