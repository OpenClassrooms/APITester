<?php

declare(strict_types=1);

namespace APITester\Tests\Util;

use APITester\Definition\Api;
use APITester\Definition\Loader\DefinitionLoader;
use APITester\Definition\Loader\OpenApiDefinitionLoader;
use APITester\Util\Object_;
use PHPUnit\Framework\TestCase;

final class Object_Test extends TestCase
{
    public function testGetSubTypesOf(): void
    {
        $subTypes = array_map(
            static fn (\ReflectionClass $class) => $class->getName(),
            Object_::getSubTypesOf(DefinitionLoader::class)
        );
        static::assertContains(OpenApiDefinitionLoader::class, $subTypes);

        $subTypes = array_map(
            static fn (\ReflectionClass $class) => $class->getName(),
            Object_::getSubTypesOf(Api::class)
        );
        static::assertEmpty($subTypes);
    }
}
