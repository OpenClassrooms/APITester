<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

final class OpenApiExamplesTestCasesPreparator extends TestCasesPreparator
{
    /**
     * @return TestCase[]
     */
    protected function generateTestCases(Operations $operations): array
    {
        $testCases = [];
        foreach ($operations->where('responses.*') as $operation) {
            $requests = $this->buildRequests($operation);
            $responses = $this->buildResponses($operation);
            $testCases[] = $this->buildTestCases(
                $requests,
                $responses,
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
        $requests = [];
        foreach ($operation->getRequests() as $request) {
            foreach ($request->getExamples() as $example) {
                $requests[$example->getName()] = new Request(
                    $operation->getMethod(),
                    $operation->getPath(),
                    [
                        'content-type' => $request->getMediaType(),
                    ],
                    Json::encode((array) $example->getValue()),
                );
            }
        }

        foreach ($operation->getPathParameters() as $parameter) {
            foreach ($parameter->getExamples() as $example) {
                $name = $example->getName();
                $requests[$name] ??= new Request(
                    $operation->getMethod(),
                    $operation->getPath(),
                );
                $requests[$name]->withUri(
                    new Uri(
                        $operation->getPath(
                            [
                                $parameter->getName() => $example->getValue(),
                            ]
                        )
                    )
                );
            }
        }

        foreach ($operation->getQueryParameters() as $parameter) {
            foreach ($parameter->getExamples() as $example) {
                $name = $example->getName();
                $requests[$name] ??= new Request(
                    $operation->getMethod(),
                    $operation->getPath(),
                );
                $request = $requests[$name];
                $request->withUri(
                    new Uri(
                        $operation->getPath(
                            [
                                $parameter->getName() => $example->getValue(),
                            ]
                        )
                    )
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
                $name = $example->getName();
                $responses[$name] = new Response(
                    $response->getStatusCode(),
                    [
                        'content-type' => $response->getMediaType(),
                    ],
                    Json::encode($example)
                );
                foreach ($response->getHeaders() as $header) {
                    /** @var ParameterExample|null $example */
                    $example = $header->getExamples()
                        ->where('name', $name)
                        ->first();
                    if (null === $example) {
                        continue;
                    }
                    $responses[$name] = $responses[$name]->withAddedHeader(
                        $header->getName(),
                        $example->getValue()
                    );
                }
            }
        }

        return $responses;
    }

    /**
     * @param array<string, Request>  $requests
     * @param array<string, Response> $responses
     *
     * @return TestCase[]
     */
    private function buildTestCases(array $requests, array $responses): array
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
            );
            $testCases[] = $fixture;
        }

        return $testCases;
    }
}
