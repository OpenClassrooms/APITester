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

    public function __construct(
        private string $name,
        Operation $parent = null,
        ?int $statusCode = null,
    ) {
        if ($parent !== null) {
            $this->parent = $parent;
        }
        $this->response = new ResponseExample($statusCode !== null ? "{$statusCode}" : null);
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

    public function setParameter(
        string $name,
        mixed $value,
        string $in,
        ?string $type = null,
        bool $deepObject = false
    ): self {
        $paramProp = $this->getParametersProp($in);

        if ($deepObject && \is_array($value)) {
            foreach ($value as $attribute => $attributeValue) {
                $this->{$paramProp}[$name][$attribute] = (string) $attributeValue;
            }

            return $this;
        }

        if (\is_array($value)) {
            $value = implode(',', $value);
        }
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        if ($type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }
        $this->{$paramProp}[$name] = (string) $value;

        return $this;
    }

    public function hasParameter(string $name, string $in): bool
    {
        $paramProp = $this->getParametersProp($in);

        return isset($this->{$paramProp}[$name]);
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

    public static function create(string $name, ?Operation $operation = null, ?int $statusCode = null): self
    {
        return new self($name, $operation, $statusCode);
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
        if ($from === Parameter::TYPE_PATH) {
            return $this->getPathParameters();
        }
        if ($from === Parameter::TYPE_QUERY) {
            return $this->getQueryParameters();
        }
        if ($from === Parameter::TYPE_HEADER) {
            return $this->getHeaders();
        }

        throw new \InvalidArgumentException("Invalid from {$from}");
    }

    /**
     * @return array<string, int|string>
     */
    public function getPathParameters(): array
    {
        if ($this->parent !== null) {
            $definitionParamsCount = $this->parent
                ->getPathParameters()
                ->count()
            ;
            if ($this->forceRandom || ($this->autoComplete && \count($this->pathParameters) < $definitionParamsCount)) {
                $randomPathParams = $this->parent
                    ->getPathParameters()
                    ->getRandomExamples()
                ;
                $this->pathParameters = array_merge($randomPathParams, $this->pathParameters);
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
        if ($this->parent !== null) {
            $definitionParamsCount = $this->parent
                ->getQueryParameters()
                ->count()
            ;
            if ($this->forceRandom || ($this->autoComplete && \count(
                $this->queryParameters
            ) < $definitionParamsCount)) {
                $randomQueryParams = $this->parent
                    ->getQueryParameters()
                    ->getRandomExamples()
                ;
                $this->queryParameters = array_merge($randomQueryParams, $this->queryParameters);
            }
        }

        return $this->queryParameters;
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    public function setQueryParameters(array $queryParameters): self
    {
        foreach ($queryParameters as $name => $value) {
            $this->setQueryParameter($name, $value);
        }

        return $this;
    }

    /**
     * @return array<string, int|string>
     */
    public function getHeaders(): array
    {
        if ($this->getBody() !== null && !isset($this->headers['content-type'])) {
            $this->headers['content-type'] = $this
                ->getBody()
                ->getMediaType()
            ;
        }

        if ($this->parent !== null) {
            $definitionHeadersCount = $this->parent
                ->getHeaders()
                ->count() + 1 // content-type
            ;

            if ($this->parent->getSecurities()->count() > 0) {
                ++$definitionHeadersCount; // Authorization
            }

            if ($this->forceRandom || ($this->autoComplete && \count($this->headers) < $definitionHeadersCount)) {
                $randomHeaders = $this->parent
                    ->getHeaders()
                    ->getRandomExamples([
                        'content-type',
                        'authorization',
                        'range',
                    ])
                ;
                $this->headers = array_merge($randomHeaders, $this->headers);
            }
        }

        return $this->headers;
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    public function getBody(): ?BodyExample
    {
        if ($this->parent === null) {
            return $this->body;
        }

        if ($this->parent->getRequestBodies()->count() === 0) {
            return null;
        }

        $requestBody = $this->parent->getBody();

        if ($requestBody === null) {
            return null;
        }

        if ($this->forceRandom) {
            return BodyExample::create($requestBody->getRandomContent());
        }

        if ($this->autoComplete) {
            $randomBody = BodyExample::create($requestBody->getRandomContent());
            if ($this->body !== null) {
                $this->body->setContent(array_merge($randomBody->getContent(), $this->body->getContent()));
            } else {
                $this->body = $randomBody;
            }
        }

        return $this->body;
    }

    public function setBody(?BodyExample $body): self
    {
        if ($body !== null) {
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

    public function setAuthenticationHeaders(Tokens $tokens, bool $ignoreScope = false): self
    {
        $operation = $this->getParent();
        if ($operation === null) {
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
                $token = $tokens->filter(
                    static fn (Token $token) => $token->getFilters()?->includes($operation) ?? false
                )
                    ->first() ?? $tokens->where('scopes', 'includes', $scopes)
                    ->first()
                ;
            }

            if ($token !== null) {
                $headers = $this->getAuthenticationParams($security, $token);
                foreach ($headers['headers'] ?? [] as $header => $value) {
                    $this->setHeader($header, $value);
                }

                foreach ($headers['query'] ?? [] as $query => $value) {
                    $this->setQueryParameter($query, $value);
                }

                return $this;
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

    public function setHeader(string $name, mixed $value): self
    {
        $this->setParameter($name, $value, 'header');

        return $this;
    }

    public function setQueryParameter(string $name, mixed $value): self
    {
        $this->setParameter($name, $value, 'query');

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
        if ($this->method !== null) {
            return $this->method;
        }
        if ($this->parent !== null) {
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
        if ($this->parent !== null && $this->forceRandom) {
            $example = $this->parent
                ->getRandomExample()
            ;
        } elseif ($this->parent !== null && $this->autoComplete) {
            $example = $this->parent
                ->getExample()
            ;
        }

        if ($example !== null && (\count($pathParameters) === 0 || $this->forceRandom)) {
            $pathParameters = $example->getPathParameters();
        }

        if ($example !== null && (\count($this->queryParameters) === 0 || $this->forceRandom)) {
            $queryParameters = $example->getQueryParameters();
        }

        if ($this->path !== null) {
            return Operation::formatPath(
                $this->path,
                $pathParameters,
                $queryParameters
            );
        }
        if ($this->parent !== null) {
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

    public function getStringBody(): string
    {
        if ($this->getBody() === null) {
            return '{}';
        }

        return $this->getBody()
            ->getStringContent()
        ;
    }

    private function getParametersProp(string $type): string
    {
        if ($type === 'path') {
            $paramProp = 'pathParameters';
        } elseif ($type === 'header') {
            $paramProp = 'headers';
        } elseif ($type === 'query') {
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
            if ($security->getIn() === 'header') {
                return [
                    'headers' => [
                        $security->getKeyName() => $token->getAccessToken(),
                    ],
                ];
            }
            if ($security->getIn() === 'cookie') {
                return [
                    'headers' => [
                        'Cookie' => "{$security->getKeyName()}={$token->getAccessToken()}",
                    ],
                ];
            }
            if ($security->getIn() === 'query') {
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
