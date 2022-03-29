<?php

declare(strict_types=1);

namespace APITester\Definition\Loader;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Collection\Parameters;
use APITester\Definition\Collection\Requests;
use APITester\Definition\Collection\Responses;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\ParameterExample;
use APITester\Definition\Request;
use APITester\Definition\RequestExample;
use APITester\Definition\Response;
use APITester\Definition\ResponseExample;

final class ExamplesExtensionLoader
{
    /**
     * @param array<array-key, mixed> $fixtures
     */
    public static function load(array $fixtures, Operations $operations): Operations
    {
        return $operations->map(
            fn (Operation $operation) => static::appendFixtureExamples(
                $operation,
                static::getMatchingFixtures($operation, $fixtures)
            )
        );
    }

    /**
     * @param array<string, array<array-key, mixed>> $fixtures
     */
    private static function appendFixtureExamples(Operation $operation, array $fixtures): Operation
    {
        $parameters = $operation->getParameters();
        $requests = $operation->getRequests();
        $responses = $operation->getResponses();

        foreach ($fixtures as $fixtureName => $fixture) {
            if (!isset($fixture['request'], $fixture['response'])
                || !\is_array($fixture['request'])
                || !\is_array($fixture['response'])
            ) {
                continue;
            }
            $parameters = static::appendParameterExamples($parameters, $fixtureName, $fixture);
            $requests = self::appendRequestExamples(
                $requests,
                $fixtureName,
                $fixture['request']['body'] ?? null
            );
            $responses = self::appendResponseExamples($responses, $fixtureName, $fixture['response']);
        }

        return $operation
            ->setParameters($parameters)
            ->setRequests($requests)
            ->setResponses($responses)
        ;
    }

    /**
     * @param array<array-key, mixed> $fixtures
     *
     * @return array<array-key, array<array-key, mixed>>
     */
    private static function getMatchingFixtures(Operation $operation, array $fixtures): array
    {
        return array_filter(
            $fixtures,
            static fn ($f) => \is_array($f)
                && static::fixtureMatchesOperation($operation, $f)
        );
    }

    /**
     * @param array<string, Parameters> $parameters
     * @param array<array-key, mixed>   $fixture
     *
     * @return array<string, Parameters>
     */
    private static function appendParameterExamples(array $parameters, string $fixtureName, array $fixture): array
    {
        if (!isset($fixture['request']) || !\is_array($fixture['request'])) {
            return $parameters;
        }

        foreach (Parameter::TYPES as $type) {
            $parameters[$type] = $parameters[$type]->map(
                fn (Parameter $p) => static::appendParameterExample(
                    $p,
                    $fixtureName,
                    $fixture['request'][$type] ?? $fixture['request']['parameters'][$type] ?? null
                )
            );
        }

        return $parameters;
    }

    /**
     * @param array{mediaType: string, content: array<array-key, mixed>}|null $requestBody
     */
    private static function appendRequestExamples(
        Requests $requests,
        string $fixtureName,
        ?array $requestBody
    ): Requests {
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
    private static function appendResponseExamples(
        Responses $responses,
        string $fixtureName,
        array $response
    ): Responses {
        return $responses->map(fn (Response $r) => static::appendResponseExample($r, $fixtureName, $response));
    }

    /**
     * @param mixed $fixture
     */
    private static function fixtureMatchesOperation(Operation $operation, $fixture): bool
    {
        if (!\is_array($fixture) || !isset($fixture['operationId'])) {
            return false;
        }

        return $fixture['operationId'] === $operation->getId();
    }

    /**
     * @param array<string, string> $fixture
     */
    private static function appendParameterExample(Parameter $p, string $fixtureName, ?array $fixture): Parameter
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
     * @param array<array-key, mixed> $fixtureResponse
     */
    private static function appendResponseExample(
        Response $response,
        string $fixtureName,
        array $fixtureResponse
    ): Response {
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
                        fn (Parameter $h) => static::appendParameterExample(
                            $h,
                            $fixtureName,
                            $fixtureResponse['header']
                        )
                    )
            );
        }

        return $response;
    }
}
