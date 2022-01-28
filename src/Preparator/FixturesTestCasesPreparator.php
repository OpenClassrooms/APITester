<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use Symfony\Component\Yaml\Yaml;

final class FixturesTestCasesPreparator extends TestCasesPreparator
{
    private string $path = '';

    /**
     * @throws PreparatorLoadingException
     * @return TestCase[]
     */
    public function prepare(Api $api): array
    {
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

        return $this->generateTestCases($fixtures, $api->getIndexedOperations());
    }

    public static function getName(): string
    {
        return 'fixtures';
    }

    public function configure(array $rawConfig): void
    {
        if (!isset($rawConfig['path'])) {
            throw new InvalidPreparatorConfigException('Missing config param "path"');
        }
        $this->path = (string) $rawConfig['path'];
    }

    /**
     * @param array<string,
     *              array{'name': string,
     *                    'for': string[],
     *                    'request': array{
     *                          'parameters'?: array{'path'?: array<string, string>, 'query'?: array<string, string>},
     *                          'headers'?: array<string, string>, 'body'?: array<mixed>},
     *                    'expectedResponse'?: array{'statusCode'?: int, 'headers'?: array<string>, 'body'?: array<mixed>}
     *              }> $fixtures
     * @param array<string, array<array-key, Operation>> $operations
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
                $operations = $operations[$group];

                foreach ($operations as $operation) {
                    if (isset($item['request']['parameters'])) {
                        $operation->getPath(
                            array_column($item['request']['parameters'], 'path'),
                            $item['request']['parameters']['query'],
                        );
                    }
                    $testCase = new TestCase(
                        $key,
                        new Request(
                            $operation->getMethod(),
                            new Uri($operation->getMethod()),
                            $item['request']['headers'] ?? [],
                            $this->formatBody($item['request']['body'] ?? []),
                        ),
                        new Response(
                            $item['expectedResponse']['statusCode'] ?? 200,
                            $item['expectedResponse']['headers'] ?? [],
                            $this->formatBody($item['expectedResponse']['body'] ?? []),
                        ),
                        $this->getGroups(
                            $operation,
                        ),
                    );

                    if (!isset($item['expectedResponse']) || !\array_key_exists('body', $item['expectedResponse'])) {
                        $testCase->addExcludedFields(['stream']);
                    }

                    $testCases[] = $testCase;
                }
            }
        }

        return $testCases;
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
