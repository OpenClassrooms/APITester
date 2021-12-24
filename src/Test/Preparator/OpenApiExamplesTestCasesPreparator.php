<?php

declare(strict_types=1);

namespace OpenAPITesting\Test\Preparator;

use cebe\openapi\spec\Header;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

final class OpenApiExamplesTestCasesPreparator implements TestCasesPreparator
{
    /**
     * @return array<TestCase>
     */
    public function __invoke(OpenApi $openApi): array
    {
        $testCases = [];
        /** @var string $path */
        foreach ($openApi->paths as $path => $pathInfo) {
            /** @var string $method */
            foreach ($pathInfo->getOperations() as $method => $operation) {
                if (null === $operation->responses) {
                    continue;
                }
                $requests = $this->buildRequests($operation, $method, $path);
                $responses = $this->buildResponses($operation);
                $testCases[] = $this->buildTestCases($requests, $responses, [$operation->operationId]);
            }
        }

        return array_merge(...$testCases);
    }

    public function getName(): string
    {
        return 'examples';
    }

    /**
     * @return array<string, Request>
     */
    private function buildRequests(Operation $operation, string $method, string $path): array
    {
        $requests = [];
        if (null !== $operation->requestBody) {
            /** @var RequestBody $requestBody */
            $requestBody = $operation->requestBody;
            $examples = $this->getExamples($requestBody->content['application/json']);
            foreach ($examples as $expectedResponse => $body) {
                $requests[$expectedResponse] = new Request(
                    mb_strtoupper($method),
                    $path . '?1=1',
                    [],
                    Json::encode($body),
                );
            }
        }

        /** @var Parameter $parameter */
        foreach ($operation->parameters as $parameter) {
            /** @var array<string, array<string|int>> $examples */
            $examples = $this->getExamples($parameter);
            foreach ($examples as $expectedResponse => $example) {
                $requests[$expectedResponse] ??= new Request(
                    mb_strtoupper($method),
                    $path . '?1=1'
                );
                $requests[$expectedResponse] = $this->addParameterToRequest(
                    $requests[$expectedResponse],
                    $parameter,
                    (string) $example[0],
                );
            }
        }

        return $requests;
    }

    /**
     * @return array<string, Response>
     */
    private function buildResponses(Operation $operation): array
    {
        if (! isset($operation->responses)) {
            return [];
        }
        $responses = [];
        foreach ($operation->responses as $statusCode => $response) {
            /** @var MediaType $content */
            $content = $response->content['application/json'];
            $examples = $this->getExamples($content, (string) $statusCode, false);
            foreach ($examples as $label => $body) {
                $key = $statusCode === $label ? $statusCode : $statusCode . '.' . $label;
                /** @var int $code */
                $code = $body['code'] ?? 0;
                $responses[$key] = new Response(
                    (int) $statusCode ?: $code,
                    [],
                    Json::encode($body)
                );
                /** @var Header $value */
                foreach ($response->headers as $name => $value) {
                    /** @var string $example */
                    $example = $value->example;
                    $responses[$key] = $responses[$key]->withAddedHeader($name, $example);
                }
            }
        }

        return $responses;
    }

    /**
     * @param array<string, Request>  $requests
     * @param array<string, Response> $responses
     * @param string[]                $groups
     *
     * @return TestCase[]
     */
    private function buildTestCases(array $requests, array $responses, array $groups): array
    {
        $testCases = [];
        foreach ($requests as $key => $request) {
            if ('default' === $key) {
                $key = (string) array_key_first($responses);
            } else {
                $key = str_replace('expects ', '', $key);
            }
            $fixture = new TestCase(
                $request,
                $responses[$key],
                $groups,
                $key,
            );
            $testCases[] = $fixture;
        }

        return $testCases;
    }

    private function addParameterToRequest(Request $request, Parameter $parameter, string $example): Request
    {
        if ('query' === $parameter->in) {
            $newRequest = $request->withUri(
                new Uri(((string) $request->getUri()) . "&{$parameter->name}={$example}")
            );
        } elseif ('path' === $parameter->in) {
            $newRequest = $request->withUri(
                new Uri(
                    str_replace(
                        "%7B{$parameter->name}%7D",
                        $example,
                        (string) $request->getUri()
                    )
                )
            );
        } else {
            $newRequest = $request->withAddedHeader($parameter->name, $example);
        }

        return $newRequest;
    }

    /**
     * @param MediaType|Schema|Parameter $object
     *
     * @return array<string, mixed[]>
     */
    private function getExamples(object $object, string $defaultKey = 'default', bool $useSummary = true): array
    {
        $examples = [];

        if (isset($object->example)) {
            $examples[$defaultKey] = (array) $object->example;
        }

        if (isset($object->example)) {
            $examples[$defaultKey] = (array) $object->example;
        }

        if (\is_object($object->example) && isset($object->example->value)) {
            $examples[$defaultKey] = (array) $object->example->value;
        }

        if (isset($object->schema->example)) {
            $examples[$defaultKey] = (array) $object->schema->example;
        }

        if (isset($object->examples)) {
            foreach ($object->examples as $label => $example) {
                $key = (string) ($useSummary ? $example->summary : $label);
                $examples[$key] = (array) $example->value;
            }
        }

        return $examples;
    }
}
