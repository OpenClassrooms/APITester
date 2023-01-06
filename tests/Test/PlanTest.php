<?php

declare(strict_types=1);

namespace APITester\Tests\Test;

use APITester\Config\Loader\PlanConfigLoader;
use APITester\Test\Exception\SuiteNotFoundException;
use APITester\Test\Plan;
use APITester\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 * @group integration
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
    }

    public function testOC(): void
    {
        $config = PlanConfigLoader::load(FixturesLocation::CONFIG_OPENAPI);
        $this->testPlan->execute($config, 'oc');
    }
}
