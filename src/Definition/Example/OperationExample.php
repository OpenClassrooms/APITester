<?php

declare(strict_types=1);

namespace APITester\Definition\Example;

use APITester\Definition\Collection\Tokens;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\Security;
use APITester\Definition\Security\ApiKeySecurity;
use APITester\Definition\Security\HttpSecurity;
use APITester\Definition\Security\OAuth2\OAuth2Security;
use APITester\Definition\Token;
use DeepCopy\DeepCopy;
use DeepCopy\TypeFilter\ShallowCopyFilter;
use DeepCopy\TypeMatcher\TypeMatcher;
use Nyholm\Psr7\Request;

final class OperationExample
{
    private string $name;

    private ?Operation $parent = null;

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

    private bool $autoComplete = true;

    private bool $forceRandom = false;

    private ?string $path = null;

    private DeepCopy $deepCopy;

    public function __construct(string $name, Operation $parent = null)
    {
        $this->name = $name;
        if (null !== $parent) {
            $this->parent = $parent;
        }
        $this->response = new ResponseExample();
        $this->deepCopy = new DeepCopy();
        $this->deepCopy->addTypeFilter(
            new ShallowCopyFilter(),
            new TypeMatcher(Operation::class)
        );
    }

    public function setPathParameter(string $name, string $value): self
    {
        $this->pathParameters[$name] = $value;

        return $this;
    }

    public function withParameter(string $name, string $value, string $in): self
    {
        /** @var self $clone */
        $clone = $this->deepCopy->copy($this);

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
        $response->setParent($this);
        $this->response = $response;

        return $this;
    }

    public function setStatusCode(string $statusCode): self
    {
        $this->response->setStatusCode($statusCode);

        return $this;
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

    public static function create(string $name, Operation $operation = null): self
    {
        return new self($name, $operation);
    }

    public function withBody(BodyExample $body): self
    {
        /** @var self $clone */
        $clone = $this->deepCopy->copy($this);
        $clone->setBody($body);

        return $clone;
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

    /**
     * @return array<string, int|string>
     */
    public function getParametersFrom(string $from): array
    {
        if (Parameter::TYPE_PATH === $from) {
            return $this->getPathParameters();
        }
        if (Parameter::TYPE_QUERY === $from) {
            return $this->getQueryParameters();
        }
        if (Parameter::TYPE_HEADER === $from) {
            return $this->getHeaders();
        }

        throw new \InvalidArgumentException("Invalid from {$from}");
    }

    /**
     * @return array<string, int|string>
     */
    public function getPathParameters(): array
    {
        if (null !== $this->parent) {
            $definitionParamsCount = $this->parent
                ->getPathParameters()
                ->count()
            ;
            if ($this->forceRandom || ($this->autoComplete && \count($this->pathParameters) < $definitionParamsCount)) {
                return $this->parent
                    ->getPathParameters()
                    ->getRandomExamples()
                ;
            }
        }

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
        if (null !== $this->parent) {
            $definitionParamsCount = $this->parent
                ->getQueryParameters()
                ->count()
            ;
            if ($this->forceRandom || ($this->autoComplete && \count(
                $this->queryParameters
            ) < $definitionParamsCount)) {
                return $this->parent
                    ->getQueryParameters()
                    ->getRandomExamples()
                ;
            }
        }

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
        if (null === $this->parent) {
            return $this->body;
        }

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

    public function setAutoComplete(bool $autoComplete = true): self
    {
        $this->autoComplete = $autoComplete;

        return $this;
    }

    public function setForceRandom(bool $forceRandom = true): self
    {
        $this->forceRandom = $forceRandom;

        return $this;
    }

    public function authenticate(Tokens $tokens, bool $ignoreScope = false): self
    {
        $operation = $this->getParent();
        if (null === $operation) {
            return $this;
        }
        foreach ($operation->getSecurities() as $security) {
            $scopes = $security->getScopes()
                ->where('name', '!=', 'current_user')
                ->select('name')
                ->toArray()
            ;

            if ($ignoreScope) {
                /** @var Token|null $token */
                $token = $tokens->first();
            } else {
                /** @var Token|null $token */
                $token = $tokens->where(
                    'scopes',
                    'includes',
                    $scopes
                )->first()
                ;
            }

            if (null !== $token) {
                $headers = $this->getAuthenticationParams($security, $token);
                foreach ($headers['headers'] ?? [] as $header => $value) {
                    $this->setHeader($header, $value);
                }

                foreach ($headers['query'] ?? [] as $query => $value) {
                    $this->setQueryParameter($query, $value);
                }
            }
        }

        return $this;
    }

    public function getParent(): ?Operation
    {
        return $this->parent;
    }

    public function setParent(Operation $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function setQueryParameter(string $name, string $value): self
    {
        $this->queryParameters[$name] = $value;

        return $this;
    }

    public function getPsrRequest(): Request
    {
        return new Request(
            $this->getMethod(),
            $this->getPath(),
            $this->getHeaders(),
            $this->getStringBody(),
        );
    }

    public function getMethod(): string
    {
        if (null !== $this->method) {
            return $this->method;
        }
        if (null !== $this->parent) {
            return $this->parent->getMethod();
        }

        return 'GET';
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

        $example = null;
        if (null !== $this->parent && $this->forceRandom) {
            $example = $this->parent
                ->getRandomExample()
            ;
        } elseif (null !== $this->parent && $this->autoComplete) {
            $example = $this->parent
                ->getExample()
            ;
        }

        if (null !== $example && (0 === \count($pathParameters) || $this->forceRandom)) {
            $pathParameters = $example->getPathParameters();
        }

        if (null !== $example && (0 === \count($this->queryParameters) || $this->forceRandom)) {
            $queryParameters = $example->getQueryParameters();
        }

        if (null !== $this->path) {
            return Operation::formatPath(
                $this->path,
                $pathParameters,
                $queryParameters
            );
        }
        if (null !== $this->parent) {
            return $this->parent->getPath(
                $pathParameters,
                $queryParameters
            );
        }

        return '';
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
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

    /**
     * @return array{headers?: array<string, string>, query?: array<string, string>}
     */
    private function getAuthenticationParams(Security $security, Token $token): array
    {
        if ($security instanceof HttpSecurity && $security->isBasic()) {
            return [
                'headers' => [
                    'Authorization' => "Basic {$token->getAccessToken()}",
                ],
            ];
        }
        if ($security instanceof HttpSecurity && $security->isBearer()) {
            return [
                'headers' => [
                    'Authorization' => "Bearer {$token->getAccessToken()}",
                ],
            ];
        }
        if ($security instanceof OAuth2Security) {
            return [
                'headers' => [
                    'Authorization' => "Bearer {$token->getAccessToken()}",
                ],
            ];
        }
        if ($security instanceof ApiKeySecurity) {
            if ('header' === $security->getIn()) {
                return [
                    'headers' => [
                        $security->getKeyName() => $token->getAccessToken(),
                    ],
                ];
            }
            if ('cookie' === $security->getIn()) {
                return [
                    'headers' => [
                        'Cookie' => "{$security->getKeyName()}={$token->getAccessToken()}",
                    ],
                ];
            }
            if ('query' === $security->getIn()) {
                return [
                    'query' => [
                        $security->getKeyName() => $token->getAccessToken(),
                    ],
                ];
            }
        }

        return [];
    }
}
