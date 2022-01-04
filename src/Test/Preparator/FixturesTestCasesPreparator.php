<?php

declare(strict_types=1);

namespace OpenAPITesting\Test\Preparator;

use cebe\openapi\spec\OpenApi;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use Symfony\Component\Yaml\Yaml;

final class FixturesTestCasesPreparator implements TestCasesPreparator
{
    private string $path = '';

    public function __invoke(OpenApi $openApi): array
    {
        $operations = $this->getIndexedOperations($openApi);
        /**
         * @var array<string,
         *              array{'name': string,
         *                    'for': array<string>,
         *                    'request': array{'headers'?: array<string>, 'body'?: array<mixed>},
         *                    'expectedResponse'?: array{'statusCode'?: int, 'headers'?: array<string>, 'body'?: array<mixed>}
         *              }> $fixtures
         */
        $fixtures = Yaml::parseFile($this->path);

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
     *                    'request': array{'headers'?: array<string>, 'body'?: array<mixed>},
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
                    throw new PreparatorLoadingException(self::getName(), new \RuntimeException(
                        "Group {$group} not found."
                    ));
                }
                $operation = $operations[$group];
                $testCases[] = new TestCase(
                    $key,
                    new Request(
                        mb_strtoupper($operation['method']),
                        new Uri($operation['path']),
                        $item['request']['headers'] ?? [],
                        Json::encode($item['request']['body'] ?? []),
                    ),
                    new Response(
                        $item['expectedResponse']['statusCode'] ?? 200,
                        $item['expectedResponse']['headers'] ?? [],
                        Json::encode($item['expectedResponse']['body'] ?? [])
                    ),
                    [
                        $operation['operation']->operationId,
                        $operation['method'],
                        ...$operation['operation']->tags,
                    ]
                );
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
}
