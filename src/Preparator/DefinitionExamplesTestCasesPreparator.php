<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Config\Preparator;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Loader\Exception\InvalidExampleFixturesException;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Preparator\Config\DefinitionExamples;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

/**
 * @property DefinitionExamples&Preparator $config
 */
final class DefinitionExamplesTestCasesPreparator extends TestCasesPreparator
{
    public static function getName(): string
    {
        return 'examples';
    }

    /**
     * @throws InvalidExampleFixturesException
     *
     * @return TestCase[]
     */
    protected function generateTestCases(Operations $operations): array
    {
//        $operations = (new FixturesLoader())
//            ->load(Yaml::concatFromDirectory($this->getFixturesPath()))
//            ->append($operations)
//        ;

        $testCases = [];
        foreach ($operations->where('responses.*', '!==', null) as $operation) {
            $requests = $this->buildRequests($operation);
            $responses = $this->buildResponses($operation);
            $testCases[] = $this->buildTestCases(
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
                    $response->getStatusCode()
                );
                if (null !== $example->getValue()) {
                    $responses[$name] = $responses[$name]->withBody(Stream::create(Json::encode($example->getValue())));
                }
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
     * @param array<string, Request> $requests
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

    private function getFixturesPath(): string
    {
        return $this->config->getFixturesPath();
    }
}
