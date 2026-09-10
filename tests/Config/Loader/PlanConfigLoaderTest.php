<?php

declare(strict_types=1);

namespace APITester\Tests\Config\Loader;

use APITester\Runtime\Config\Exception\ConfigurationException;
use APITester\Runtime\Config\Loader\PlanConfigLoader;
use APITester\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;

final class PlanConfigLoaderTest extends TestCase
{
    public function testLoadReturnsPlanWithSuites(): void
    {
        $plan = PlanConfigLoader::load(FixturesLocation::CONFIG_OPENAPI);
        $suite = $plan->getSuites()[0];

        static::assertNotEmpty($plan->getSuites());
        static::assertSame('oc', $suite->getName());
        static::assertSame('openapi', $suite->getDefinition()->getFormat());
        static::assertStringEndsWith(
            '/tests/Fixtures/OpenAPI/openclassrooms-api.yml',
            $suite->getDefinition()
                ->getPath()
        );
        static::assertSame('http-async', $suite->getRequester());
        static::assertSame([
            [
                'id' => 'oc_api_learning_activity_learning_path_projects_with_user_information_get',
            ],
            [
                'tags.*.name' => 'Invitation',
            ],
        ], $suite->getFilters()
            ->getInclude());

        $preparators = $suite->getPreparators();

        static::assertArrayHasKey('examples', $preparators);
        static::assertSame(
            'tests/Fixtures/Examples/petstore/examples.new.yml',
            $preparators['examples']['extensionPath']
        );

        $authentications = $suite->getAuthentications();

        static::assertCount(2, $authentications);
        static::assertSame('user_1', $authentications[0]->getName());
        static::assertSame('password', $authentications[0]->getBody()['grant_type']);
        static::assertSame('application/json', $authentications[0]->getHeaders()['Accept']);
        static::assertNull($authentications[0]->getFilters());
        static::assertSame('user_2', $authentications[1]->getName());
        static::assertSame('client_credentials', $authentications[1]->getBody()['grant_type']);
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
