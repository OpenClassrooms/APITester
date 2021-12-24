<?php

declare(strict_types=1);

namespace OpenAPITesting\Config\Loader;

use OpenAPITesting\Config\DefinitionConfig;
use OpenAPITesting\Config\PlanConfig;
use OpenAPITesting\Config\SuiteConfig;
use Symfony\Component\Yaml\Yaml;

final class PlanConfigLoader
{
    public function __invoke(string $path): PlanConfig
    {
        /** @var array{'suites': array<string, array{'definition': array{'path': string, 'format': string}, 'preparators': array<string>, 'groups': ?array<string>}>} $yml */
        $yml = Yaml::parseFile($path);
        $suiteConfigs = [];
        foreach ($yml['suites'] as $suiteTitle => $suite) {
            $suiteConfigs[] = new SuiteConfig(
                $suiteTitle,
                new DefinitionConfig(
                    getcwd() . '/' . trim($suite['definition']['path'], '/'),
                    $suite['definition']['format'],
                ),
                $suite['preparators'],
                $suite['groups'] ?? [],
            );
        }

        return new PlanConfig($suiteConfigs);
    }
}
