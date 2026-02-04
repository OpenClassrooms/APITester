<?php

declare(strict_types=1);

namespace APITester\Tests\Config\Loader;

use APITester\Config\Exception\ConfigurationException;
use APITester\Config\Loader\PlanConfigLoader;
use APITester\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;

final class PlanConfigLoaderTest extends TestCase
{
    public function testLoadReturnsPlanWithSuites(): void
    {
        $plan = PlanConfigLoader::load(FixturesLocation::CONFIG_OPENAPI);

        static::assertNotEmpty($plan->getSuites());
        static::assertSame('oc', $plan->getSuites()[0]->getName());
    }

    public function testLoadThrowsForNonExistentFile(): void
    {
        $this->expectException(ConfigurationException::class);

        PlanConfigLoader::load('/non/existent/file.yaml');
    }

    public function testEnvVarSubstitution(): void
    {
        $_ENV['TEST_API_VAR'] = 'replaced_value';

        try {
            $content = '%env(TEST_API_VAR)%';
            $ref = new \ReflectionClass(PlanConfigLoader::class);
            $method = $ref->getMethod('process');

            $result = $method->invoke(null, $content);

            static::assertSame('replaced_value', $result);
        } finally {
            unset($_ENV['TEST_API_VAR']);
        }
    }

    public function testMissingEnvVarThrowsConfigurationException(): void
    {
        unset($_ENV['NONEXISTENT_VAR_FOR_TEST']);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("'NONEXISTENT_VAR_FOR_TEST'");

        $ref = new \ReflectionClass(PlanConfigLoader::class);
        $method = $ref->getMethod('process');

        $method->invoke(null, '%env(NONEXISTENT_VAR_FOR_TEST)%');
    }
}
