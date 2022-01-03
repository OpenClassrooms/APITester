<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class PlanConfig
{
    /**
     * @var array<SuiteConfig>
     */
    private array $testSuiteConfigs;

    /**
     * @param array<SuiteConfig> $testSuiteConfigs
     */
    public function __construct(array $testSuiteConfigs)
    {
        $this->testSuiteConfigs = $testSuiteConfigs;
    }

    /**
     * @return array<SuiteConfig>
     */
    public function getTestSuiteConfigs(): array
    {
        return $this->testSuiteConfigs;
    }
}
