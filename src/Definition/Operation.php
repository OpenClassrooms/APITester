<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition;

use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Collection\Requests;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Collection\Securities;
use OpenAPITesting\Definition\Collection\Tags;

final class Operation
{
    private Api $parent;

    private string $id;

    private string $path;

    private string $method;

    private Parameters $pathParameters;

    private Parameters $queryParameters;

    private Parameters $headers;

    private Requests $requests;

    private Responses $responses;

    private string $summary = '';

    private string $description = '';

    private Tags $tags;

    private Securities $securities;

    private string $preparator;

    public function __construct(
        string $id,
        string $path,
        string $method
    ) {
        $this->id = $id;
        $this->path = $path;
        $this->method = $method;
        $this->pathParameters = new Parameters();
        $this->queryParameters = new Parameters();
        $this->headers = new Parameters();
        $this->requests = new Requests();
        $this->responses = new Responses();
        $this->tags = new Tags();
        $this->securities = new Securities();
    }

    public static function create(string $id, string $path, string $method = 'GET'): self
    {
        return new static($id, $path, $method);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getExamplePath(
        Parameters $pathParameters = null,
        Parameters $queryParameters = null
    ): string {
        if (null === $pathParameters) {
            $pathParameters = $this->getPathParameters();
        }
        if (null === $queryParameters) {
            $queryParameters = $this->getQueryParameters();
        }

        return $this->getPath(
            $pathParameters->toExampleArray(),
            $queryParameters->toExampleArray()
        );
    }

    public function getPathParameters(bool $onlyRequired = false): Parameters
    {
        return $this->pathParameters;
    }

    public function setPathParameters(Parameters $parameters): self
    {
        foreach ($parameters as $param) {
            $param->setParent($this);
        }
        $this->pathParameters = $parameters;

        return $this;
    }

    public function getQueryParameters(): Parameters
    {
        return $this->queryParameters;
    }

    public function setQueryParameters(Parameters $parameters): self
    {
        foreach ($parameters as $param) {
            $param->setParent($this);
        }
        $this->queryParameters = $parameters;

        return $this;
    }

    /**
     * @param array<string|int, string|int> $params
     * @param array<string|int, string|int> $query
     */
    public function getPath(array $params = [], array $query = [], string $providedPath = null): string
    {
        $params = $this->substituteParams($params, 'path');
        $query = $this->substituteParams($query, 'query');
        $path = str_replace(
            array_map(
                static fn (string $name) => "{{$name}}",
                array_keys($params),
            ),
            array_values($params),
            $providedPath ?? $this->path
        );

        return rtrim($path . '?' . http_build_query($query), '?');
    }

    public function addParameterExample(?ParameterExample $example, string $type, string $name): self
    {
        if (null === $example) {
            return $this;
        }

        $parameters = $this->getParameters(false)[$type];
        $parameters->map(
            function (Parameter $p) use ($example, $name) {
                if ($name === $p->getName()) {
                    $p->addExample($example);
                }

                return $p;
            }
        );

        return $this->setParametersByType($parameters, $type);
    }

    /**
     * @return array<string, Parameters>
     */
    public function getParameters(bool $required = true): array
    {
        $parameters = [
            Parameter::TYPE_PATH => $this->getPathParameters(),
            Parameter::TYPE_QUERY => $this->getQueryParameters(),
            Parameter::TYPE_HEADER => $this->getHeaders(),
        ];

        if ($required) {
            $parameters = array_map(static fn (Parameters $p) => $p->where('required', true), $parameters);
        }

        return $parameters;
    }

    public function setParametersByType(Parameters $parameters, string $type): self
    {
        if (Parameter::TYPE_PATH === $type) {
            $this->pathParameters = $parameters;
        } elseif (Parameter::TYPE_QUERY === $type) {
            $this->queryParameters = $parameters;
        } elseif (Parameter::TYPE_HEADER === $type) {
            $this->headers = $parameters;
        }

        return $this;
    }

    /**
     * @param array<string, Parameters> $parameters
     */
    public function setParameters(array $parameters): self
    {
        foreach (Parameter::TYPES as $type) {
            $this->setParametersByType($parameters[$type], $type);
        }

        return $this;
    }

    public function getHeaders(): Parameters
    {
        return $this->headers;
    }

    public function setHeaders(Parameters $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function addRequestExample(?RequestExample $example, string $mediaType): self
    {
        if (null === $example) {
            return $this;
        }

        $requests = $this->getRequests();
        $requests->map(
            function (Request $r) use ($example, $mediaType) {
                if ($mediaType === $r->getMediaType()) {
                    $r->addExample($example);
                }

                return $r;
            }
        );

        return $this->setRequests($requests);
    }

    public function getRequests(): Requests
    {
        return $this->requests;
    }

    public function setRequests(Requests $requests): self
    {
        foreach ($requests as $request) {
            $request->setParent($this);
        }
        $this->requests = $requests;

        return $this;
    }

    public function addResponseExample(int $statusCode, ?ResponseExample $example, ?string $mediaType): self
    {
        if (null === $example) {
            return $this;
        }

        $responses = $this->getResponses();

        if (0 === $responses->where('statusCode', $statusCode)->count()) {
            return $this->addResponse(Response::create($statusCode)->addExample($example));
        }

        $responses->map(
            function (Response $r) use ($example, $mediaType) {
                if ($mediaType === $r->getMediaType()) {
                    $r->addExample($example);
                }

                return $r;
            }
        );

        return $this->setResponses($responses);
    }

    public function getResponses(): Responses
    {
        return $this->responses;
    }

    public function setResponses(Responses $responses): self
    {
        foreach ($responses as $response) {
            $response->setParent($this);
        }
        $this->responses = $responses;

        return $this;
    }

    public function getMethod(): string
    {
        return mb_strtoupper($this->method ?? 'GET');
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function addRequest(Request $request): self
    {
        $request->setParent($this);
        $this->requests->add($request);

        return $this;
    }

    public function getRequest(string $mediaType): ?Request
    {
        /** @var Request|null */
        return $this->requests->firstWhere('mediaType', $mediaType);
    }

    public function addPathParameter(Parameter $parameter): self
    {
        $parameter->setParent($this);
        $this->pathParameters->add($parameter);

        return $this;
    }

    public function addQueryParameter(Parameter $parameter): self
    {
        $parameter->setParent($this);
        $this->queryParameters->add($parameter);

        return $this;
    }

    public function addHeader(Parameter $header): self
    {
        $header->setParent($this);
        $this->headers->add($header);

        return $this;
    }

    public function addResponse(Response $response): self
    {
        $response->setParent($this);
        $this->responses->add($response);

        return $this;
    }

    public function getTags(): Tags
    {
        return $this->tags;
    }

    public function setTags(Tags $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function addQueryParameters(Parameter $parameter): self
    {
        $this->queryParameters->add($parameter);

        return $this;
    }

    public function getSecurities(): Securities
    {
        return $this->securities;
    }

    public function setSecurities(Securities $securities): self
    {
        foreach ($securities as $security) {
            $security->setParent($this);
        }
        $this->securities = $securities;

        return $this;
    }

    public function addSecurity(Security $security): self
    {
        $security->setParent($this);
        $this->securities->add($security);

        return $this;
    }

    public function getParent(): Api
    {
        return $this->parent;
    }

    public function setParent(Api $parent): void
    {
        $this->parent = $parent;
    }

    public function getPreparator(): string
    {
        return $this->preparator;
    }

    public function setPreparator(string $string): self
    {
        $this->preparator = $string;

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function has(string $prop, $value): bool
    {
        $operation = collect([$this]);
        $operator = '=';
        if (str_contains($prop, '*')) {
            $operator = 'contains';
        }

        return null !== $operation->where($prop, $operator, $value)
            ->first()
        ;
    }

    /**
     * @param array<int|string, string|int> $params
     *
     * @return array<string, string|int>
     */
    private function substituteParams(array $params, string $in): array
    {
        $prop = "{$in}Parameters";
        if (!isset($this->{$prop})) {
            throw new \RuntimeException("Parameters in {$in} not handled.");
        }
        /** @var Parameters $parameters */
        $parameters = $this->{$prop};
        $result = [];
        foreach ($params as $name => $value) {
            if (\is_string($name)) {
                $result[$name] = $value;
            } else {
                if (!isset($parameters[$name])) {
                    continue;
                }
                /** @var Parameter $parameter */
                $parameter = $parameters[$name];
                $result[$parameter->getName()] = $value;
            }
        }

        return $result;
    }
}
