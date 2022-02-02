<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

use OpenAPITesting\Config\Exception\ConfigurationException;
use OpenAPITesting\Requester\HttpAsyncRequester;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

/**
 * @phpstan-type AuthsConfig array<string, array{
 *                                          'name': string,
 *                                          'username'?: ?string,
 *                                          'password'?: ?string,
 *                                          'type': string,
 *                                          'headers'?: array<string, string>,
 *                                          'scopes'?: array<string>,
 * }>
 * @phpstan-type SuitesConfig array{
 *  'suites': array<string, array{
 *              'definition': array{'path': string, 'format': string},
 *              'preparators'?: ?array<string, array<string, mixed>>,
 *              'requester'?: ?string,
 *              'filters'?: ?array{'include': ?string[], 'exclude': ?string[]},
 *              'auth'?: AuthsConfig,
 *              'callbacks'?: ?array{'beforeTestCase': string[], 'afterTestCase': string[]}
 *             }
 *            >
 * }
 */
final class PlanConfig
{
    /**
     * @var array<SuiteConfig>
     */
    private array $testSuiteConfigs = [];

    private ?object $callbackObject;

    /**
     * @throws ConfigurationException
     */
    public function __construct(string $path, ?object $callbackObject = null)
    {
        $this->callbackObject = $callbackObject;
        $dotenv = new Dotenv();
        $dotenv->loadEnv(PROJECT_DIR . '/env/.env');
        /** @var mixed[] $data */
        $data = Yaml::parseFile($path);

        /** @var SuitesConfig $data */
        $data = $this->process($data);
        foreach ($data['suites'] as $suiteTitle => $suite) {
            $callbacks = $this->callableFromConfig($suite['callbacks'] ?? []);
            $this->testSuiteConfigs[] = new SuiteConfig(
                $suiteTitle,
                new DefinitionConfig(
                    $suite['definition']['path'],
                    $suite['definition']['format'],
                ),
                $suite['preparators'] ?? [],
                $suite['requester'] ?? HttpAsyncRequester::getName(),
                isset($suite['auth']) ? $this->getAuthConfigs($suite['auth']) : [],
                new FiltersConfig(
                    $suite['filters']['include'] ?? [],
                    $suite['filters']['exclude'] ?? [],
                ),
                $callbacks['beforeTestCase'] ?? [],
                $callbacks['afterTestCase'] ?? [],
            );
        }
    }

    /**
     * @return array<SuiteConfig>
     */
    public function getTestSuiteConfigs(): array
    {
        return $this->testSuiteConfigs;
    }

    /**
     * @param mixed[] $data
     *
     * @throws ConfigurationException
     *
     * @return mixed[]
     */
    private function process(array $data): array
    {
        foreach ($data as &$value) {
            if (\is_array($value)) {
                $value = $this->process($value);
            }
            if (!\is_string($value)) {
                continue;
            }
            if (preg_match_all('/%env\((.+?)\)%/i', $value, $matches) > 0) {
                foreach ($matches[1] as $var) {
                    $env = getenv($var);
                    if (false === $env) {
                        throw new ConfigurationException("Environment variable '{$var}' is not defined.");
                    }
                    /** @var string $value */
                    $value = preg_replace('/%env\((.+?)\)%/i', $env, $value);
                }
            }
        }

        return $data;
    }

    /**
     * @param array{beforeTestCase?: string[], afterTestCase?: string[]} $allCallbacks
     *
     * @return array{beforeTestCase?: \Closure[], afterTestCase?: \Closure[]}
     */
    private function callableFromConfig(array $allCallbacks): array
    {
        $closures = [];
        foreach ($allCallbacks as $type => $callbacks) {
            foreach ($callbacks as $callback) {
                if (null !== $this->callbackObject) {
                    $callback = [$this->callbackObject, $callback];
                }
                /** @var callable $callback */
                $closures[$type][] = \Closure::fromCallable($callback);
            }
        }

        return $closures;
    }

    /**
     * @param AuthsConfig $auth
     *
     * @return array<AuthConfig>
     */
    private function getAuthConfigs(array $auth): array
    {
        $configs = [];
        foreach ($auth as $name => $data) {
            $configs[] = new AuthConfig(
                $name,
                $data['type'],
                $data['username'] ?? null,
                $data['password'] ?? null,
                $data['scopes'] ?? [],
                $data['headers'] ?? [],
            );
        }

        return $configs;
    }
}
