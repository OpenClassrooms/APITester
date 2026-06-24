<?php

declare(strict_types=1);

namespace APITester\Tests\Definition\Loader;

use APITester\Definition\Loader\OpenApiDefinitionLoader;
use APITester\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;

final class OpenApiDefinitionLoaderTest extends TestCase
{
    public function testSchemaLevelArrayExampleTakesPrecedence(): void
    {
        $api = (new OpenApiDefinitionLoader())->load(
            FixturesLocation::OPEN_API_WITH_SCHEMA_EXAMPLE
        );

        $operation = $api->getOperations()
            ->first(
                static fn ($op) => $op->getId() === 'createArrayBody'
            );
        $body = $operation->getExample()
            ->getBody();

        self::assertNotNull($body);
        self::assertSame(
            [
                [
                    'id' => 5656,
                    'evaluationCriteria' => 'KDKKE Ipsum',
                ],
            ],
            $body->getContent()
        );
    }

    public function testSchemaLevelObjectExampleTakesPrecedence(): void
    {
        $api = (new OpenApiDefinitionLoader())->load(
            FixturesLocation::OPEN_API_WITH_SCHEMA_EXAMPLE
        );

        $operation = $api->getOperations()
            ->first(
                static fn ($op) => $op->getId() === 'createObjectBody'
            );
        $body = $operation->getExample()
            ->getBody();

        self::assertNotNull($body);
        self::assertSame(
            [
                'id' => 5656,
                'evaluationCriteria' => 'KDKKE Ipsum',
            ],
            $body->getContent()
        );
    }
}
