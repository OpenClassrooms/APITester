<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use cebe\openapi\spec\OpenApi;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use Symfony\Component\Yaml\Yaml;

final class FixturesTestCasesPreparator extends TestCasesPreparator
{
    private string $path = '';

    public function prepare(OpenApi $openApi): array
    {
        $operations = $this->getIndexedOperations($openApi);
        /**
         * @var array<string,
         *              array{'name': string,
         *                    'for': array<string>,
         *                    'request': array{
         *                          'parameters'?: array{'path'?: array<string, string>,'query'?: array<string, string>},
         *                          'headers'?: array<string, string>, 'body'?: array<mixed>},
         *                    'expectedResponse'?: array{'statusCode'?: int, 'headers'?: array<string>, 'body'?: array<mixed>}
         *              }> $fixtures
         */
        $fixtures = Yaml::parseFile(PROJECT_DIR . '/' . $this->path);

        return $this->generateTestCases($fixtures, $operations);
    }

    public static function getName(): string
    {
        return 'fixtures';
    }

    public function configure(array $config): void
    {
        if (!isset($config['path'])) {
            throw new InvalidPreparatorConfigException('Missing config param "path"');
        }
        $this->path = (string) $config['path'];
    }

    /**
     * @param array<string,
     *              array{'name': string,
     *                    'for': array<string>,
     *                    'request': array{
     *                          'parameters'?: array{'path'?: array<string, string>, 'query'?: array<string, string>},
     *                          'headers'?: array<string, string>, 'body'?: array<mixed>},
     *                    'expectedResponse'?: array{'statusCode'?: int, 'headers'?: array<string>, 'body'?: array<mixed>}
     *              }> $fixtures
     * @param array<string, array{'operation': \cebe\openapi\spec\Operation, 'path': string, 'method': string}> $operations
     *
     * @throws PreparatorLoadingException
     *
     * @return array<TestCase>
     */
    private function generateTestCases(array $fixtures, array $operations): array
    {
        $testCases = [];

        foreach ($fixtures as $key => &$item) {
            $item['__construct'] = false;
            $item['id'] = uniqid('testcases_', false);

            $groups = $item['for'];
            foreach ($groups as $group) {
                if (!isset($operations[$group])) {
                    throw new PreparatorLoadingException(
                        self::getName(),
                        new \RuntimeException("Group {$group} not found."),
                    );
                }
                $operation = $operations[$group];
                if (isset($item['request']['parameters'])) {
                    foreach ($item['request']['parameters']['path'] ?? [] as $name => $parameter) {
                        $operation['path'] = str_replace("{{$name}}", $parameter, $operation['path']);
                    }
                    if (isset($item['request']['parameters']['query'])) {
                        $operation['path'] .= '?' . http_build_query($item['request']['parameters']['query']);
                    }
                }
                $testCase = new TestCase(
                    $key,
                    new Request(
                        mb_strtoupper($operation['method']),
                        new Uri($operation['path']),
                        $item['request']['headers'] ?? [],
                        $this->formatBody($item['request']['body'] ?? []),
                    ),
                    new Response(
                        $item['expectedResponse']['statusCode'] ?? 200,
                        $item['expectedResponse']['headers'] ?? [],
                        $this->formatBody($item['expectedResponse']['body'] ?? []),
                    ),
                    $this->getGroups(
                        $operation['operation'],
                        $operation['method']
                    ),
                );

                if (!isset($item['expectedResponse']) || !\array_key_exists('body', $item['expectedResponse'])) {
                    $testCase->addExcludedFields(['stream']);
                }

                $testCases[] = $testCase;
            }
        }

        return $testCases;
    }

    /**
     * @return array<string, array{'operation': \cebe\openapi\spec\Operation, 'path': string, 'method': string}>
     */
    private function getIndexedOperations(OpenApi $openApi): array
    {
        $indexes = [];
        foreach ($openApi->paths as $path => $pathInfo) {
            foreach ($pathInfo->getOperations() as $method => $operation) {
                $data = [
                    'operation' => $operation,
                    'path' => (string) $path,
                    'method' => (string) $method,
                ];
                $indexes[(string) $method] = $data;
                $indexes[$operation->operationId] = $data;
                foreach ($operation->tags as $tag) {
                    $indexes[$tag] = $data;
                }
            }
        }

        return $indexes;
    }

    /**
     * @param array<mixed>|string $body
     */
    private function formatBody($body): string
    {
        if (\is_array($body)) {
            return Json::encode($body);
        }

        return $body;
    }
}
