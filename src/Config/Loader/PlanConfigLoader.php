<?php

declare(strict_types=1);

namespace OpenAPITesting\Config\Loader;

use OpenAPITesting\Config\DefinitionConfig;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Config\SuiteConfig;
use OpenAPITesting\Requester\HttpAsyncRequester;
use OpenAPITesting\Test\Filters;
use Symfony\Component\Yaml\Yaml;

final class PlanConfigLoader
{
    private ?object $callbackObject = null;

    public function __invoke(string $path, ?object $callbackObject = null): PlanConfig
    {
        $this->callbackObject = $callbackObject;
        /**
         * @var array{
         *  'suites': array<string, array{
         *              'definition': array{'path': string, 'format': string},
         *              'preparators': ?array<string>,
         *              'requester': ?string,
         *              'filters': ?array{'include': ?string[], 'exclude': ?string[]},
         *              'callback': ?array{'beforeTestCase': ?string, 'afterTestCase': ?string}
         *              }
         *            >
         * } $yml
         */
        $yml = Yaml::parseFile($path);
        $suiteConfigs = [];
        foreach ($yml['suites'] as $suiteTitle => $suite) {
            $callbacks = $this->callableFromConfig($suite['callback'] ?? []);
            $suiteConfigs[] = new SuiteConfig(
                $suiteTitle,
                new DefinitionConfig(
                    getcwd() . '/' . trim($suite['definition']['path'], '/'),
                    $suite['definition']['format'],
                ),
                $suite['preparators'] ?? [],
                $suite['requester'] ?? HttpAsyncRequester::getName(),
                new Filters(
                    $suite['filters']['include'] ?? [],
                    $suite['filters']['exclude'] ?? [],
                ),
                $callbacks['beforeTestCase'] ?? null,
                $callbacks['afterTestCase'] ?? null,
            );
        }

        return new PlanConfig($suiteConfigs);
    }

    /**
     * @param array{beforeTestCase?: ?string, afterTestCase?: ?string} $callbacks
     *
     * @return array{beforeTestCase?: ?\Closure, afterTestCase?: ?\Closure}
     */
    private function callableFromConfig(array $callbacks): array
    {
        $closures = [];
        foreach ($callbacks as $type => $callback) {
            if (null !== $this->callbackObject) {
                $callback = [$this->callbackObject, $callback];
            }
            /** @var callable $callback */
            $closures[$type] = \Closure::fromCallable($callback);
        }

        return $closures;
    }
}
