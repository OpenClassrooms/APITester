<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use Symfony\Component\Yaml\Yaml;

final class FixturesTestCasesPreparator extends TestCasesPreparator
{
    private string $path = '';

    /**
     * @throws PreparatorLoadingException
     *
     * @return TestCase[]
     */
    protected function generateTestCases(Operations $operations): array
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

        return $this->prepareTestCases($fixtures, $operations->toPropIndexedArray());
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
     * @param array<string, Operation[]> $groupedOperations
     *
     * @throws PreparatorLoadingException
     *
     * @return array<TestCase>
     */
    private function prepareTestCases(array $fixtures, array $groupedOperations): array
    {
        $testCases = [];

        foreach ($fixtures as $key => &$item) {
            $item['__construct'] = false;
            $item['id'] = uniqid('testcases_', false);

            $groups = $item['for'];
            foreach ($groups as $group) {
                if (!isset($groupedOperations[$group])) {
                    throw new PreparatorLoadingException(
                        self::getName(),
                        new \RuntimeException("Group {$group} not found."),
                    );
                }
                $operations = $groupedOperations[$group];

                foreach ($operations as $operation) {
                    if (isset($item['request']['parameters'])) {
                        $operation->getPath(
                            array_column($item['request']['parameters'], 'path'),
                            $item['request']['parameters']['query'] ?? [],
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
