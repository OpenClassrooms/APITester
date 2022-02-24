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

final class FixturesLoader
{
    /**
     * @param array<array-key, mixed> $fixtures
     */
    public function load(array $fixtures, Operations $operations): Operations
    {
        return $operations->map(
            fn (Operation $operation) => $this->appendFixtureExamples(
                $operation,
                $this->getMatchingFixtures($operation, $fixtures)
            )
        );
    }

    /**
     * @param array<array-key, mixed> $fixtures
     *
     * @return array<array-key, array<array-key, mixed>>
     */
    private function getMatchingFixtures(Operation $operation, array $fixtures): array
    {
        return array_filter($fixtures, fn ($f) => \is_array($f) && $this->fixtureMatchesOperation($operation, $f));
    }

    /**
     * @param mixed $fixture
     */
    private function fixtureMatchesOperation(Operation $operation, $fixture): bool
    {
        if (!\is_array($fixture) || !isset($fixture['operationId'])) {
            return false;
        }

        return $fixture['operationId'] === $operation->getId();
    }

    /**
     * @param array<string, array<array-key, mixed>> $fixtures
     */
    private function appendFixtureExamples(Operation $operation, array $fixtures): Operation
    {
        $parameters = $operation->getParameters(false);
        $requests = $operation->getRequests();
        $responses = $operation->getResponses();

        foreach ($fixtures as $fixtureName => $fixture) {
            if (!isset($fixture['request'], $fixture['response'])
                || !\is_array($fixture['request'])
                || !\is_array($fixture['response'])
            ) {
                continue;
            }
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

    /**
     * @param array<string, Parameters> $parameters
     * @param array<array-key, mixed>   $fixture
     *
     * @return array<string, Parameters>
     */
    private function appendParameterExamples(array $parameters, string $fixtureName, array $fixture): array
    {
        if (!isset($fixture['request']) || !\is_array($fixture['request'])) {
            return $parameters;
        }

        foreach (Parameter::TYPES as $type) {
            $parameters[$type] = $parameters[$type]->map(
                fn (Parameter $p) => $this->appendParameterExample(
                    $p,
                    $fixtureName,
                    $fixture['request'][$type] ?? $fixture['request']['parameters'][$type] ?? null
                )
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
     * @param array<array-key, mixed> $response
     */
    private function appendResponseExamples(Responses $responses, string $fixtureName, array $response): Responses
    {
        return $responses->map(fn (Response $r) => $this->appendResponseExample($r, $fixtureName, $response));
    }

    /**
     * @param array<array-key, mixed> $fixtureResponse
     */
    private function appendResponseExample(Response $response, string $fixtureName, array $fixtureResponse): Response
    {
        if (\is_array($fixtureResponse['body'])
            && $response->getMediaType() === $fixtureResponse['body']['mediaType']
            && $response->getStatusCode() === $fixtureResponse['statusCode']
        ) {
            $response->addExample(
                new ResponseExample($fixtureName, $fixtureResponse['body']['content'])
            );
        }

        if (isset($fixtureResponse['header']) && \is_array($fixtureResponse['header'])) {
            $response->setHeaders(
                $response->getHeaders()
                    ->map(
                        fn (Parameter $h) => $this->appendParameterExample($h, $fixtureName, $fixtureResponse['header'])
                    )
            );
        }

        return $response;
    }
}
