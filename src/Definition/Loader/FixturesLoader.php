<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Loader;

use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Parameters;
use OpenAPITesting\Definition\Loader\Exception\InvalidExampleFixturesException;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\Request;
use OpenAPITesting\Definition\Response;
use OpenAPITesting\Util\Object_;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

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
     * @var array<array-key, array<array-key, array<array-key, Parameters>>>
     */
    private array $parameters = [];

    /**
     * @var array<array-key, array<array-key, Response>>
     */
    private array $responses = [];

    /**
     * @var array<array-key, array<array-key, Request>>
     */
    private array $requests = [];

    /**
     * @param array<array-key, FixtureFormat> $fixtures
     *
     * @throws InvalidExampleFixturesException
     */
    public function load(array $fixtures): self
    {
        foreach ($fixtures as $name => $fixture) {
            $fixture = $this->prepare($name, $fixture);

            try {
                $fixtureParameters = [];
                foreach (Parameter::TYPES as $type) {
                    if (!isset($fixture[$type])) {
                        continue;
                    }
                    $fixtureParameters[$type] = Object_::fromArray(
                        $fixture[$type],
                        Parameters::class
                    );
                }

                if (isset($fixture['request'])) {
                    /** @var Request $fixtureRequest */
                    $fixtureRequest = Object_::fromArray(
                        $fixture['request'],
                        Request::class
                    );
                }

                /** @var Response $fixtureResponse */
                $fixtureResponse = Object_::fromArray(
                    $fixture['response'],
                    Response::class
                );
            } catch (ExceptionInterface $e) {
                throw new InvalidExampleFixturesException(static::class, 0, $e);
            }

            $this->parameters[$fixture['operationId']][$name] = $fixtureParameters;
            if (isset($fixtureRequest)) {
                $this->requests[$fixture['operationId']][$name] = $fixtureRequest;
            }
            $this->responses[$fixture['operationId']][$name] = $fixtureResponse;
        }

        return $this;
    }

    public function append(Operations $operations): Operations
    {
        return $operations->map(
            fn (Operation $o) => $this->appendToOperation($o)
        );
    }

    private function appendToOperation(Operation $operation): Operation
    {
        $parameters = $this->parameters[$operation->getId()] ?? null;
        $requests = $this->requests[$operation->getId()] ?? [];
        $responses = $this->responses[$operation->getId()] ?? null;

        if (null === $parameters || null === $responses) {
            return $operation;
        }

        return $operation->addExamples($parameters, $requests, $responses);
    }

    /**
     * @param FixtureFormat $fixture
     *
     * @return array{
     *      operationId: string,
     *      path?: array{ items: array<array-key, array{
     *          name: string,
     *          examples: array{items: array<array-key, array{name: string, value: string }>}
     *      }>},
     *      query?: array{ items: array<array-key, array{
     *          name: string,
     *          examples: array{items: array<array-key, array{name: string, value: string }>}
     *      }>},
     *      header?: array{ items: array<array-key, array{
     *          name: string,
     *          examples: array{items: array<array-key, array{name: string, value: string }>}
     *      }>},
     *      request?: array{
     *          mediaType: string,
     *          body: array<array-key, mixed>,
     *          examples: array{items: {array<array-key, array{
     *              name: string,
     *              value: array<array-key, mixed>
     *          }>}}
     *      },
     *      response: array{
     *          statusCode: int,
     *          headers?: array{items: array<array-key, array{
     *              name: string,
     *              examples: array{items: array<array-key, array{
     *                  name: string,
     *                  value: string
     *              }>}
     *          }>},
     *          mediaType?: string,
     *          body?: array<array-key, mixed>,
     *          examples: array{items: {array<array-key, array{
     *              name: string,
     *              value: array<array-key, mixed>
     *          }>}}
     *      }
     * }
     */
    private function prepare(string $name, array $fixture): array
    {
        $result = [
            'operationId' => $fixture['operationId'],
        ];
        foreach (Parameter::TYPES as $type) {
            if (!isset($fixture['request'][$type]) || [] === $fixture['request'][$type]) {
                continue;
            }
            $result[$type]['items'] = [];
            foreach ($fixture['request'][$type] as $parameter => $value) {
                $result[$type]['items'][] = [
                    'name' => $parameter,
                    'examples' => [
                        'items' => [
                            [
                                'name' => $name,
                                'value' => $value,
                            ],
                        ],
                    ],
                ];
            }
        }

        if (isset($fixture['request']['body'])) {
            $result['request'] = [
                'mediaType' => $fixture['request']['body']['mediaType'],
                'body' => [
                    'data' => [],
                ],
                'examples' => [
                    'items' => [
                        [
                            'name' => $name,
                            'value' => $fixture['request']['body']['content'],
                        ],
                    ],
                ],
            ];
        }

        $result['response'] = [
            'statusCode' => $fixture['response']['statusCode'],
        ];

        if (isset($fixture['response']['header']) && [] !== $fixture['response']['header']) {
            $result['response']['headers']['items'] = [];
            foreach ($fixture['response']['header'] as $parameter => $value) {
                $result['response']['headers']['items'][] = [
                    'name' => $parameter,
                    'examples' => [
                        'items' => [
                            [
                                'name' => $name,
                                'value' => $value,
                            ],
                        ],
                    ],
                ];
            }
        }

        if (isset($fixture['response']['body'])) {
            $result['response']['body'] = [
                'data' => [],
            ];
            $result['response']['mediaType'] = $fixture['response']['body']['mediaType'];
            $result['response']['examples']['items'] = [
                [
                    'name' => $name,
                    'value' => $fixture['response']['body']['content'],
                ],
            ];
        }

        return $result;
    }
}
