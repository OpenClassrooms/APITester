<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

final class OpenApiExamplesTestCasesPreparator extends TestCasesPreparator
{
    /**
     * @return TestCase[]
     */
    public function prepare(Api $api): array
    {
        $testCases = [];
        foreach ($api->getOperations() as $operation) {
            if (0 === \count($operation->getResponses())) {
                continue;
            }
            $requests = $this->buildRequests($operation);
            $responses = $this->buildResponses($operation);
            $testCases[] = $this->buildTestCases(
                $requests,
                $responses,
                $this->getGroups($operation),
            );
        }

        return array_filter(array_merge(...$testCases));
    }

    public static function getName(): string
    {
        return 'examples';
    }

    /**
     * @return array<string, Request>
     */
    private function buildRequests(Operation $operation): array
    {
        $defaultHeaders = null !== $this->token ? [
            'authorization' => "Bearer {$this->token}",
        ] : [];
        $requests = [];
        foreach ($operation->getRequests() as $request) {
            foreach ($request->getExamples() as $example) {
                $requests[$example->getName()] = new Request(
                    $operation->getMethod(),
                    $operation->getPath() . '?1=1',
                    array_merge(
                        [
                            'content-type' => $request->getMediaType(),
                        ],
                        $defaultHeaders
                    ),
                    Json::encode($example->getValue()),
                );
            }
        }

        foreach ($operation->getPathParameters() as $parameter) {
            foreach ($parameter->getExamples() as $example) {
                $index = $example->getName();
                $requests[$index] ??= new Request(
                    $operation->getMethod(),
                    $operation->getPath() . '?1=1',
                    $defaultHeaders,
                );
                $requests[$index] = $this->addParameterToRequest(
                    $requests[$index],
                    $parameter,
                    $example->getValue(),
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
        $responses = [];
        foreach ($operation->getResponses() as $response) {
            foreach ($response->getExamples() as $example) {
                $index = $example->getName();
                $responses[$index] = new Response(
                    $response->getStatusCode(),
                    [
                        'content-type' => $response->getMediaType(),
                    ],
                    Json::encode($example)
                );
                foreach ($response->getHeaders() as $header) {
                    $responses[$index] = $responses[$index]->withAddedHeader(
                        $header->getName(),
                        $example
                    );
                }
            }
        }

        return $responses;
    }

    /**
     * @param array<string, Request> $requests
     * @param array<string, Response> $responses
     * @param string[] $groups
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
                $key,
                $request,
                $responses[$key] ?? new Response(),
                $groups,
            );
            $testCases[] = $fixture;
        }

        return $testCases;
    }

    private function addParameterToRequest(Request $request, Parameter $parameter, string $example): Request
    {
        if ('query' === $parameter->getIn()) {
            $newRequest = $request->withUri(
                new Uri(((string) $request->getUri()) . "&{$parameter->getName()}={$example}")
            );
        } elseif ('path' === $parameter->getIn()) {
            $newRequest = $request->withUri(
                new Uri(
                    str_replace(
                        "%7B{$parameter->getName()}%7D",
                        $example,
                        (string) $request->getUri()
                    )
                )
            );
        } else {
            $newRequest = $request->withAddedHeader($parameter->getName(), $example);
        }

        return $newRequest;
    }
}
