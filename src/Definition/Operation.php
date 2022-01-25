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
    private Api $api;

    private string $id;

    private string $path;

    private ?string $method = null;

    private Parameters $pathParameters;

    private Parameters $queryParameters;

    private Requests $requests;

    private Responses $responses;

    private string $summary = '';

    private string $description = '';

    private Tags $tags;

    private Securities $securities;

    public function __construct(
        string $id,
        string $path
    ) {
        $this->id = $id;
        $this->path = $path;
        $this->pathParameters = new Parameters();
        $this->queryParameters = new Parameters();
        $this->requests = new Requests();
        $this->responses = new Responses();
        $this->tags = new Tags();
        $this->securities = new Securities();
    }

    public static function create(string $id, string $path): self
    {
        return new static($id, $path);
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param array<string|int, string|int> $params
     * @param array<string|int, string|int> $query
     *
     * @return string
     */
    public function getPath(array $params = [], array $query = []): string
    {
        $params = $this->substituteParams($params, 'path');
        $query = $this->substituteParams($query, 'query');
        $path = str_replace(
            array_map(
                static fn (string $name) => "{{$name}}",
                array_keys($params),
            ),
            array_values($params),
            $this->path
        );

        return rtrim($path . '?' . http_build_query($query), '?');
    }

    /**
     * @param array<int|string, string|int> $params
     *
     * @return array<string, string|int>
     */
    private function substituteParams(array $params, string $in): array
    {
        $prop = "{$in}Parameters";
        if (!isset($this->$prop)) {
            throw new \RuntimeException("Parameters in $in not handled.");
        }
        /** @var Parameters $parameters */
        $parameters = $this->$prop;
        $result = [];
        foreach ($params as $name => $value) {
            if (is_string($name)) {
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

    public function getMethod(): string
    {
        return mb_strtoupper($this->method ?? 'GET');
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getRequests(): Requests
    {
        return $this->requests;
    }

    public function setRequests(Requests $requests): self
    {
        $this->requests = $requests;

        return $this;
    }

    public function addRequest(Request $request): self
    {
        if ($this->method === null) {
            $this->setMethod('POST');
        }
        $request->setOperation($this);
        $this->requests->add($request);

        return $this;
    }

    public function getRequest(string $mediaType): ?Request
    {
        /** @var Request|null */
        return $this->requests->firstWhere('mediaType', $mediaType);
    }

    public function getPathParameters(): Parameters
    {
        return $this->pathParameters;
    }

    public function setPathParameters(Parameters $pathParameters): self
    {
        $this->pathParameters = $pathParameters;

        return $this;
    }

    public function addParameter(Parameter $parameter): self
    {
        $parameter->setOperation($this);
        $this->pathParameters->add($parameter);

        return $this;
    }

    public function getResponses(): Responses
    {
        return $this->responses;
    }

    public function setResponses(Responses $responses): self
    {
        $this->responses = $responses;

        return $this;
    }

    public function addResponse(Response $response): self
    {
        $response->setOperation($this);
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

    public function getQueryParameters(): Parameters
    {
        return $this->queryParameters;
    }

    public function setQueryParameters(Parameters $queryParameters): self
    {
        $this->queryParameters = $queryParameters;

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
        $this->securities = $securities;

        return $this;
    }

    public function addSecurity(Security $security): self
    {
        $security->setOperation($this);
        $this->securities->add($security);

        return $this;
    }

    public function getApi(): Api
    {
        return $this->api;
    }

    public function setApi(Api $api): void
    {
        $this->api = $api;
    }
}
