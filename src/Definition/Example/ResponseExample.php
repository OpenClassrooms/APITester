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

    /**
     * @var mixed
     */
    private $content;

    /**
     * @param mixed $content
     */
    public function __construct(?string $statusCode = null, $content = null)
    {
        $this->content = $content;
        $this->statusCode = $statusCode ?? $this->statusCode;
    }

    /**
     * @param mixed $content
     */
    public static function create(?string $statusCode = null, $content = null): self
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
        if (null !== $this->content && !isset($this->headers['content-type'])) {
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

    /**
     * @param mixed $content
     */
    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getStringContent(): string
    {
        if (null === $this->content) {
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
