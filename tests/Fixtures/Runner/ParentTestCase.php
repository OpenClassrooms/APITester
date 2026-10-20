<?php

declare(strict_types=1);

namespace APITester\Tests\Fixtures\Runner;

use PHPUnit\Framework\TestCase;

abstract class ParentTestCase extends TestCase
{
    protected function setUp(): void
    {
        static::assertNotEmpty($this->dataName());
    }

    protected function getKernel(): Kernel
    {
        $kernel = new Kernel('test', true);
        $kernel->fromParent = true;

        return $kernel;
    }
}
