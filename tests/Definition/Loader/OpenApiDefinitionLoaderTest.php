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
            )
        ;
        $body = $operation->getExample()
            ->getBody()
        ;

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

    public function testMediaTypeLevelExample(): void
    {
        $api = (new OpenApiDefinitionLoader())->load(
            FixturesLocation::OPEN_API_WITH_SCHEMA_EXAMPLE
        );

        $operation = $api->getOperations()
            ->first(
                static fn ($op) => $op->getId() === 'createMediaTypeExample'
            )
        ;
        $body = $operation->getExample()
            ->getBody()
        ;

        self::assertNotNull($body);
        self::assertSame(
            [
                'id' => 111,
                'evaluationCriteria' => 'strict evaluation',
            ],
            $body->getContent()
        );
    }

    public function testMediaTypeLevelExamples(): void
    {
        $api = (new OpenApiDefinitionLoader())->load(
            FixturesLocation::OPEN_API_WITH_SCHEMA_EXAMPLE
        );

        $operation = $api->getOperations()
            ->first(
                static fn ($op) => $op->getId() === 'createMediaTypeExamples'
            )
        ;
        $body = $operation->getExamples();
        self::assertCount(3, $body);

        self::assertSame(
            [
                'id' => 10,
                'evaluationCriteria' => 'Jessica Smith',
            ],
            $operation->getExample('criteria')
                ->getBody()
                ->getContent()
        );
        self::assertSame(
            [
                'id' => 11,
            ],
            $operation->getExample('noCriteria')
                ->getBody()
                ->getContent()
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
            )
        ;
        $body = $operation->getExample()
            ->getBody()
        ;

        self::assertNotNull($body);
        self::assertSame(
            [
                'id' => 5656,
                'evaluationCriteria' => 'KDKKE Ipsum',
            ],
            $body->getContent()
        );
    }

    public function testRootLevelExampleIsNotAutoCompletedWithRandomData(): void
    {
        $api = (new OpenApiDefinitionLoader())->load(
            FixturesLocation::OPEN_API_WITH_SCHEMA_EXAMPLE
        );

        $operation = $api->getOperations()
            ->first(
                static fn ($op) => $op->getId() === 'createMediaTypeExamples'
            )
        ;

        // The "noCriteria" example only defines "id"; being a root level example it
        // must be used verbatim and not be merged with random schema based values.
        $body = $operation->getExample('noCriteria')
            ->getBody()
        ;

        self::assertNotNull($body);
        self::assertSame(
            [
                'id' => 11,
            ],
            $body->getContent()
        );
    }

    public function testOptionalRequestBodyWithoutExampleProducesNoExamples(): void
    {
        $api = (new OpenApiDefinitionLoader())->load(
            FixturesLocation::OPEN_API_WITH_SCHEMA_EXAMPLE
        );

        $operation = $api->getOperations()
            ->first(
                static fn ($op) => $op->getId() === 'createOptionalBodyWithoutExample'
            )
        ;

        self::assertSame(0, $operation->getExamples()->count());
    }
}
