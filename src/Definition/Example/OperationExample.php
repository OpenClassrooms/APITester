<?php

declare(strict_types=1);

namespace APITester\Definition\Example;

use APITester\Definition\Operation;
use APITester\Definition\Parameter;

final class OperationExample
{
    private string $name;

    private Operation $parent;

    /**
     * @var array<string, string|int>
     */
    private array $headers = [];

    /**
     * @var array<string, string|int>
     */
    private array $pathParameters = [];

    /**
     * @var array<string, string|int>
     */
    private array $queryParameters = [];

    private ?string $method = null;

    private ?BodyExample $body = null;

    private ResponseExample $response;

    private bool $randomAutoComplete = true;

    public function __construct(string $name, Operation $parent = null)
    {
        $this->name = $name;
        if (null !== $parent) {
            $this->parent = $parent;
        }
        $this->response = new ResponseExample();
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function setPathParameter(string $name, string $value): self
    {
        $this->pathParameters[$name] = $value;

        return $this;
    }

    public function setQueryParameter(string $name, string $value): self
    {
        $this->queryParameters[$name] = $value;

        return $this;
    }

    public function withParameter(string $name, string $value, string $in): self
    {
        $clone = clone $this;
        $clone->setParameter($name, $value, $in);

        return $clone;
    }

    public function setParameter(string $name, string $value, string $in): self
    {
        $paramProp = $this->getParametersProp($in);
        $this->{$paramProp}[$name] = $value;

        return $this;
    }

    public function getResponse(): ResponseExample
    {
        return $this->response;
    }

    public function setResponse(ResponseExample $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->response->setStatusCode($statusCode);

        return $this;
    }

    /**
     * @return array<string, int|string>
     */
    public function getHeaders(): array
    {
        if (null !== $this->getBody() && !isset($this->headers['content-type'])) {
            $this->headers['content-type'] = $this
                ->getBody()
                ->getMediaType()
            ;
        }

        return $this->headers;
    }

    /**
     * @param array<string, int|string> $headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function getBody(): ?BodyExample
    {
        if (0 === $this->parent->getRequestBodies()->count()) {
            return null;
        }

        $requestBody = $this->parent->getBody();
        if (null === $this->body && null !== $requestBody) {
            return BodyExample::create($requestBody->getRandomContent());
        }

        return $this->body;
    }

    public function setBody(?BodyExample $body): self
    {
        if (null !== $body) {
            $body->setParent($this);
        }
        $this->body = $body;

        return $this;
    }

    public static function create(string $name, Operation $operation = null): self
    {
        return new self($name, $operation);
    }

    /**
     * @param mixed[] $content
     */
    public function setBodyContent(array $content): self
    {
        $body = BodyExample::create($content);
        $body->setParent($this);
        $this->body = $body;

        return $this;
    }

    public function withBody(BodyExample $body): self
    {
        $clone = clone $this;
        $clone->setBody($body);

        return $clone;
    }

    public function getStringBody(): ?string
    {
        if (null === $this->getBody()) {
            return null;
        }

        return $this->getBody()
            ->getStringContent()
        ;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method ?? $this->parent->getMethod();
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getPath(): string
    {
        $pathParameters = $this->pathParameters;
        $queryParameters = $this->queryParameters;

        if (0 === \count($pathParameters)) {
            $example = $this->getParent()
                ->getExamples()
                ->where('name', 'default')
                ->first()
            ;
            if (null !== $example) {
                $pathParameters = $example->getPathParameters();
            } elseif ($this->randomAutoComplete) {
                $pathParameters = $this->getParent()
                    ->getPathParameters()
                    ->getExamples()
                ;
            }
        }

        if (0 === \count($this->queryParameters)) {
            $example = $this->getParent()
                ->getExamples()
                ->where('name', 'default')
                ->first()
            ;
            if (null !== $example) {
                $queryParameters = $example->getQueryParameters();
            } elseif ($this->randomAutoComplete) {
                $queryParameters = $this->getParent()
                    ->getQueryParameters()
                    ->getExamples()
                ;
            }
        }

        return $this->parent->getPath($pathParameters, $queryParameters);
    }

    public function getParent(): Operation
    {
        return $this->parent;
    }

    public function setParent(Operation $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return array<string, int|string>
     */
    public function getPathParameters(): array
    {
        return $this->pathParameters;
    }

    /**
     * @param array<string, int|string> $pathParameters
     */
    public function setPathParameters(array $pathParameters): self
    {
        $this->pathParameters = $pathParameters;

        return $this;
    }

    /**
     * @return array<string, int|string>
     */
    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    /**
     * @param array<string, int|string> $queryParameters
     */
    public function setQueryParameters(array $queryParameters): self
    {
        $this->queryParameters = $queryParameters;

        return $this;
    }

    /**
     * @return array<string, int|string>
     */
    public function getParametersFrom(string $from): array
    {
        if (Parameter::TYPE_PATH === $from) {
            return $this->pathParameters;
        }
        if (Parameter::TYPE_QUERY === $from) {
            return $this->queryParameters;
        }
        if (Parameter::TYPE_HEADER === $from) {
            return $this->headers;
        }

        throw new \InvalidArgumentException("Invalid from {$from}");
    }

    public function setRandomAutoComplete(bool $randomAutoComplete = true): self
    {
        $this->randomAutoComplete = $randomAutoComplete;

        return $this;
    }

    private function getParametersProp(string $type): string
    {
        if ('path' === $type) {
            $paramProp = 'pathParameters';
        } elseif ('header' === $type) {
            $paramProp = 'headers';
        } elseif ('query' === $type) {
            $paramProp = 'queryParameters';
        } else {
            throw new \InvalidArgumentException("Invalid parameter type: {$type}");
        }

        return $paramProp;
    }
}
