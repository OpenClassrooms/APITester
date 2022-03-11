<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Loader\FixturesLoader;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Preparator\Config\DefinitionExamples;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use OpenAPITesting\Util\Yaml;

/**
 * @property DefinitionExamples $config
 */
final class DefinitionExamplesTestCasesPreparator extends TestCasesPreparator
{
    public static function getName(): string
    {
        return 'examples';
    }

    /**
     * @return TestCase[]
     */
    protected function generateTestCases(Operations $operations): array
    {
        $operations = (new FixturesLoader())
            ->load(Yaml::concatFromDirectory($this->config->additionalExamplesPath), $operations)
        ;

        $testCases = [];
        foreach ($operations->where('responses.*', '!==', null) as $operation) {
            $requests = $this->buildRequests($operation);
            $responses = $this->buildResponses($operation);
            $testCases[] = $this->buildTestCases(
                $operation,
                $requests,
                $responses,
            );
        }

        return array_filter(array_merge(...$testCases));
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
                $requests[$name] = $requests[$name]->withUri(
                    new Uri(
                        $operation->getPath(
                            [
                                $parameter->getName() => $example->getValue(),
                            ],
                            [],
                            urldecode($requests[$name]->getUri()->getPath())
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
                $requests[$name] = $requests[$name]->withUri(
                    new Uri(
                        $operation->getPath(
                            [],
                            [
                                $parameter->getName() => $example->getValue(),
                            ]
                        )
                    )
                );
            }
        }

        foreach ($operation->getHeaders() as $parameter) {
            foreach ($parameter->getExamples() as $example) {
                $name = $example->getName();
                $requests[$name] ??= new Request(
                    $operation->getMethod(),
                    $operation->getPath(),
                );
                $requests[$name] = $requests[$name]->withAddedHeader($parameter->getName(), $example->getValue());
            }
        }

        $authenticatedRequests = [];
        foreach ($requests as $name => $request) {
            $authenticatedRequests[$name] = $this->authenticate($request, $operation);
        }

        return $authenticatedRequests;
    }

    /**
     * @return array<string, Response>
     */
    private function buildResponses(Operation $operation): array
    {
        $responses = [];
        foreach ($operation->getResponses() as $response) {
            if (!isset($responses['default'])) {
                $body = $response->getExamples()[$response->getStatusCode() . '_properties'] ?? null;
                if (null !== $body) {
                    $body = Json::encode($body);
                } else {
                    $body = '#.*#';
                }
                $responses['default'] = new Response(
                    $response->getStatusCode(),
                    [],
                    $body
                );
                foreach ($response->getHeaders() as $header) {
                    /** @var ParameterExample|null $example */
                    $example = $header->getExamples()
                        ->where('name', 'default')
                        ->first()
                    ;
                    if (null === $example) {
                        continue;
                    }
                    $responses['default'] = $this->addHeaders(
                        $response,
                        $responses['default'],
                        'default'
                    );
                }
            }
            foreach ($response->getExamples() as $example) {
                $name = $example->getName();
                $responses[$name] = new Response(
                    $response->getStatusCode()
                );
                if (null !== $example->getValue()) {
                    $responses[$name] = $responses[$name]->withBody(Stream::create(Json::encode($example->getValue())));
                }
                $responses[$name] = $this->addHeaders(
                    $response,
                    $responses[$name],
                    $name
                );
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
    private function buildTestCases(Operation $operation, array $requests, array $responses): array
    {
        $testCases = [];
        foreach ($requests as $key => $request) {
            if ('default' === $key) {
                $key = \array_key_exists('default', $responses) ? 'default' : array_key_first($responses);
            } elseif ('properties' === $key) {
                if (\array_key_exists('properties_200', $responses)) {
                    $key = 'properties_200';
                } elseif (\array_key_exists('properties_201', $responses)) {
                    $key = 'properties_201';
                } else {
                    $key = array_key_first($responses);
                }
            } else {
                $key = str_replace('expects ', '', $key);
            }
            $fixture = new TestCase(
                "operation: {$operation->getId()} example: {$key}",
                $request,
                $responses[$key],
            );
            $testCases[] = $fixture;
        }

        return $testCases;
    }

    private function addHeaders(
        DefinitionResponse $definitionResponse,
        Response $response,
        string $exampleName
    ): Response {
        foreach ($definitionResponse->getHeaders() as $header) {
            /** @var ParameterExample|null $example */
            $example = $header->getExamples()
                ->where('name', $exampleName)
                ->first()
            ;
            if (null === $example) {
                continue;
            }

            $response = $response->withAddedHeader(
                $header->getName(),
                $example->getValue()
            );
        }

        return $response;
    }
}
