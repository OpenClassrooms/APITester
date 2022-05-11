<?php

declare(strict_types=1);

namespace APITester\Definition;

use APITester\Definition\Collection\Bodies;
use APITester\Definition\Collection\OperationExamples;
use APITester\Definition\Collection\Parameters;
use APITester\Definition\Collection\Responses;
use APITester\Definition\Collection\Securities;
use APITester\Definition\Collection\Tags;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;

final class Operation
{
    private string $description = '';

    private Parameters $headers;

    private string $id;

    private string $method;

    private Api $parent;

    private string $path;

    private Parameters $pathParameters;

    private string $preparator;

    private Parameters $queryParameters;

    private Bodies $bodies;

    private Responses $responses;

    private Securities $securities;

    private string $summary = '';

    private Tags $tags;

    private OperationExamples $examples;

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
        $this->bodies = new Bodies();
        $this->responses = new Responses();
        $this->tags = new Tags();
        $this->securities = new Securities();
        $this->examples = new OperationExamples();
    }

    public function addHeader(Parameter $header): self
    {
        $header->setIn(Parameter::TYPE_HEADER);
        $header->setParent($this);
        $this->headers->add($header);

        return $this;
    }

    /**
     * @return array<string, Parameters>
     */
    public function getParameters(bool $onlyRequired = false): array
    {
        $parameters = [
            Parameter::TYPE_PATH => $this->getPathParameters(),
            Parameter::TYPE_QUERY => $this->getQueryParameters(),
            Parameter::TYPE_HEADER => $this->getHeaders(),
        ];

        if ($onlyRequired) {
            $parameters = array_map(static fn (Parameters $p) => $p->where('required', true), $parameters);
        }

        return $parameters;
    }

    public function getPathParameters(bool $onlyRequired = false): Parameters
    {
        if ($onlyRequired) {
            return $this->pathParameters->where('required', true);
        }

        return $this->pathParameters;
    }

    public function setPathParameters(Parameters $parameters): self
    {
        foreach ($parameters as $param) {
            $param->setParent($this);
            $param->setIn(Parameter::TYPE_PATH);
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
            $param->setIn(Parameter::TYPE_QUERY);
        }
        $this->queryParameters = $parameters;

        return $this;
    }

    public function getHeaders(): Parameters
    {
        return $this->headers;
    }

    public function setHeaders(Parameters $headers): self
    {
        foreach ($headers as $header) {
            $header->setParent($this);
            $header->setIn(Parameter::TYPE_PATH);
        }
        $this->headers = $headers;

        return $this;
    }

    public function addExample(OperationExample $example): self
    {
        $example->setParent($this);
        $this->examples->add($example);

        return $this;
    }

    public function addPathParameter(Parameter $parameter): self
    {
        $parameter->setIn(Parameter::TYPE_PATH);
        $parameter->setParent($this);
        $this->pathParameters->add($parameter);

        return $this;
    }

    public function addQueryParameter(Parameter $parameter): self
    {
        $parameter->setIn(Parameter::TYPE_QUERY);
        $parameter->setParent($this);
        $this->queryParameters->add($parameter);

        return $this;
    }

    public function addRequestBody(Body $request): self
    {
        $request->setParent($this);
        $this->bodies->add($request);

        return $this;
    }

    public function getRequestBodies(): Bodies
    {
        return $this->bodies;
    }

    public function setRequestBodies(Bodies $bodies): self
    {
        foreach ($bodies as $request) {
            $request->setParent($this);
        }
        $this->bodies = $bodies;

        return $this;
    }

    public function getResponses(): Responses
    {
        return $this->responses;
    }

    public function getResponse(int $status): ?Response
    {
        return $this->responses->get($status);
    }

    public function setResponses(Responses $responses): self
    {
        foreach ($responses as $response) {
            $response->setParent($this);
        }
        $this->responses = $responses;

        return $this;
    }

    public function addResponse(Response $response): self
    {
        $response->setParent($this);
        $this->responses->add($response);

        return $this;
    }

    public function addSecurity(Security $security): self
    {
        $security->setParent($this);
        $this->securities->add($security);

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
            $pathParameters->getExamples(),
            $queryParameters->getExamples()
        );
    }

    /**
     * @param array<string|int, string|int> $params
     * @param array<string|int, string|int> $query
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

    public function getExamples(): OperationExamples
    {
        return $this->examples;
    }

    public function setExamples(OperationExamples $examples): self
    {
        foreach ($examples as $example) {
            $example->setParent($this);
        }
        $this->examples = $examples;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

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

    /**
     * @param array<string, Parameters> $parameters
     */
    public function setParameters(array $parameters): self
    {
        foreach (Parameter::TYPES as $type) {
            $this->setParametersIn($parameters[$type], $type);
        }

        return $this;
    }

    public function setParametersIn(Parameters $parameters, string $in): self
    {
        foreach ($parameters as $parameter) {
            $parameter->setIn($in);
        }
        if (Parameter::TYPE_PATH === $in) {
            $this->pathParameters = $parameters;
        } elseif (Parameter::TYPE_QUERY === $in) {
            $this->queryParameters = $parameters;
        } elseif (Parameter::TYPE_HEADER === $in) {
            $this->headers = $parameters;
        }

        return $this;
    }

    public function getExample(?string $name = null): OperationExample
    {
        $examples = $this->getExamples();
        if (null !== $name) {
            return $examples
                ->get($name)
            ;
        }

        $firstExampleName = (string) $examples
            ->keys()
            ->first()
        ;
        foreach (['properties', 'default', $firstExampleName] as $key) {
            if ($examples->has($key)) {
                return $examples
                    ->get($key)
                ;
            }
        }

        return $this->getRandomExample();
    }

    /**
     * @param mixed $value
     */
    public function has(string $prop, $value, string $operator = '='): bool
    {
        $operation = collect([$this]);
        if (str_contains($prop, '*')) {
            $operator = 'contains';
        }

        return null !== $operation
            ->where($prop, $operator, $value)
            ->first()
        ;
    }

    public function getRandomExample(): OperationExample
    {
        $body = $this->getBody();
        $bodyExample = null;
        if (null !== $body) {
            $bodyExample = BodyExample::create($body->getRandomContent());
        }

        return OperationExample::create('_random')
            ->setParent($this)
            ->setPathParameters($this->getPathParameters()->getRandomExamples())
            ->setQueryParameters($this->getQueryParameters()->getRandomExamples())
            ->setHeaders($this->getHeaders()->getRandomExamples())
            ->setBody($bodyExample)
        ;
    }

    public function getBody(string $mediaType = 'application/json'): ?Body
    {
        /** @var Body|null */
        return $this->bodies->firstWhere('mediaType', $mediaType);
    }

    public static function create(string $id, string $path, string $method = 'GET'): self
    {
        return new static($id, $path, $method);
    }

    public function getRandomPath(): string
    {
        $pathParameters = $this->getPathParameters();
        $queryParameters = $this->getQueryParameters();

        return $this->getPath(
            $pathParameters->getRandomExamples(),
            $queryParameters->getRandomExamples()
        );
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
