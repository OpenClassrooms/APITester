<?php

namespace OpenAPITesting\Models\OpenAPI;

class Operation extends \cebe\openapi\spec\Operation
{
    protected string $method;

    protected \cebe\openapi\spec\Operation $operation;

    protected string $path;

    public function __construct(array $data = [])
    {
        $this->operation = $data['operation'];
        $this->method = $data['method'];
        $this->path = $data['path'];
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function __call($name, $arguments)
    {
        return call_user_func([$this->operation, $name], $arguments);
    }
}