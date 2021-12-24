<?php

declare(strict_types=1);

namespace OpenAPITesting\Config;

final class TestPlanConfig
{
    /**
     * @var array<TestSuiteConfig>
     */
    private array $testSuiteConfigs;

    /**
     * @param array<TestSuiteConfig> $testSuiteConfigs
     */
    public function __construct(array $testSuiteConfigs)
    {
        $this->testSuiteConfigs = $testSuiteConfigs;
    }

    /**
     * @return array<TestSuiteConfig>
     */
    public function getTestSuiteConfigs(): array
    {
        return $this->testSuiteConfigs;
    }
}
