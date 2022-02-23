<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test;

use OpenAPITesting\Config\Loader\PlanConfigLoader;
use OpenAPITesting\Test\Exception\SuiteNotFoundException;
use OpenAPITesting\Test\Plan;
use OpenAPITesting\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 * @group integration
 * @coversDefaultClass
 */
final class PlanTest extends TestCase
{
    private Plan $testPlan;

    protected function setUp(): void
    {
        $this->testPlan = new Plan();
        $this->testPlan->setLogger(
            new ConsoleLogger(new ConsoleOutput(OutputInterface::VERBOSITY_VERY_VERBOSE))
        );
    }

    public function testPetStore(): void
    {
        $this->expectException(SuiteNotFoundException::class);
        $config = PlanConfigLoader::load(FixturesLocation::CONFIG_OPENAPI);
        $this->testPlan->execute($config, 'petstore');
        $this->testPlan->assert();
    }

    public function testOC(): void
    {
        $config = PlanConfigLoader::load(FixturesLocation::CONFIG_OPENAPI);
        $this->testPlan->execute($config, 'oc');
        $this->testPlan->assert();
    }
}
