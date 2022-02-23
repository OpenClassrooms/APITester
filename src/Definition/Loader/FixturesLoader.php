<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Loader;

use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Collection\Requests;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Definition\Request;
use OpenAPITesting\Definition\RequestExample;
use OpenAPITesting\Definition\Response;
use OpenAPITesting\Definition\ResponseExample;

/**
 * @phpstan-type FixtureFormat array{
 *      operationId: string,
 *      request: array{
 *          path?: array<string, string>,
 *          query?: array<string, string>,
 *          header?: array<string, string>,
 *          body?: array{
 *              mediaType: string,
 *              content: array<array-key, mixed>
 *          }
 *      },
 *      response: array{
 *          statusCode: int,
 *          header?: array<string, string>,
 *          body?: array{
 *              mediaType: string,
 *              content: array<array-key, mixed>
 *          }
 *      }
 * }
 */
final class FixturesLoader
{
    /**
     * @param array<string, FixtureFormat> $fixtures
     */
    public function load(array $fixtures, Operations $operations): Operations
    {
        return $operations->map(
            fn (Operation $o) => $this->appendFixtureExamples(
                $o,
                array_filter($fixtures, static fn ($f) => $f['operationId'] === $o->getId())
            )
        );
    }

    /**
     * @param array<string, Parameters> $parameters
     * @param FixtureFormat             $fixture
     *
     * @return array<string, Parameters>
     */
    private function appendParameterExamples(array $parameters, string $fixtureName, array $fixture): array
    {
        foreach (Parameter::TYPES as $type) {
            $parameters[$type] = $parameters[$type]->map(
                fn (Parameter $p) => $this->appendParameterExample($p, $fixtureName, $fixture['request'][$type] ?? null)
            );
        }

        return $parameters;
    }

    /**
     * @param array<string, string> $fixture
     */
    private function appendParameterExample(Parameter $p, string $fixtureName, ?array $fixture): Parameter
    {
        if (null === $fixture) {
            return $p;
        }

        $value = $fixture[$p->getName()] ?? null;
        if (isset($value)) {
            $p->addExample(new ParameterExample($fixtureName, $value));
        }

        return $p;
    }

    /**
     * @param array{mediaType: string, content: array<array-key, mixed>}|null $requestBody
     */
    private function appendRequestExamples(Requests $requests, string $fixtureName, ?array $requestBody): Requests
    {
        if (!isset($requestBody)) {
            return $requests;
        }

        return $requests->map(function (Request $r) use ($fixtureName, $requestBody) {
            if ($r->getMediaType() === $requestBody['mediaType']) {
                return $r->addExample(
                    new RequestExample($fixtureName, $requestBody['content'])
                );
            }

            return $r;
        });
    }

    /**
     * @param array{statusCode: int, header?: array<string, string>,body?: array{mediaType: string,content: array<array-key, mixed>}} $response
     */
    private function appendResponseExamples(Responses $responses, string $fixtureName, array $response): Responses
    {
        return $responses->map(function (Response $r) use ($fixtureName, $response) {
            if (isset($response['body'])
                && $r->getMediaType() === $response['body']['mediaType']
                && $r->getStatusCode() === $response['statusCode']
            ) {
                $r->addExample(
                    new ResponseExample($fixtureName, $response['body']['content'])
                );
            }

            if (isset($response['header'])) {
                $r->setHeaders(
                    $r->getHeaders()
                        ->map(
                            fn (Parameter $h) => $this->appendParameterExample($h, $fixtureName, $response['header'])
                        )
                );
            }

            return $r;
        });
    }

    /**
     * @param array<string, FixtureFormat> $fixtures
     */
    private function appendFixtureExamples(Operation $operation, array $fixtures): Operation
    {
        $parameters = $operation->getParameters(false);
        $requests = $operation->getRequests();
        $responses = $operation->getResponses();

        foreach ($fixtures as $fixtureName => $fixture) {
            $parameters = $this->appendParameterExamples($parameters, $fixtureName, $fixture);
            $requests = $this->appendRequestExamples(
                $requests,
                $fixtureName,
                $fixture['request']['body'] ?? null
            );
            $responses = $this->appendResponseExamples($responses, $fixtureName, $fixture['response']);
        }

        return $operation
            ->setParameters($parameters)
            ->setRequests($requests)
            ->setResponses($responses)
        ;
    }
}
