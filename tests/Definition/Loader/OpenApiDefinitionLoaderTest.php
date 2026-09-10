<?php

declare(strict_types=1);

namespace APITester\Tests\Definition\Loader;

use APITester\Schema\Entity\Api;
use APITester\Schema\Entity\Operation;
use APITester\Schema\Loader\OpenApiDefinitionLoader;
use APITester\Tests\Fixtures\FixturesLocation;
use PHPUnit\Framework\TestCase;

final class OpenApiDefinitionLoaderTest extends TestCase
{
    public function testSchemaLevelArrayExampleTakesPrecedence(): void
    {
        $api = (new OpenApiDefinitionLoader())->load(
            FixturesLocation::OPEN_API_WITH_SCHEMA_EXAMPLE
        );

        $operation = self::getOperation($api, 'createArrayBody');
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

        $operation = self::getOperation($api, 'createMediaTypeExample');
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

        $operation = self::getOperation($api, 'createMediaTypeExamples');
        $body = $operation->getExamples();
        self::assertCount(3, $body);

        $criteriaBody = $operation->getExample('criteria')
            ->getBody()
        ;
        self::assertNotNull($criteriaBody);
        self::assertSame(
            [
                'id' => 10,
                'evaluationCriteria' => 'Jessica Smith',
            ],
            $criteriaBody->getContent()
        );

        $noCriteriaBody = $operation->getExample('noCriteria')
            ->getBody()
        ;
        self::assertNotNull($noCriteriaBody);
        self::assertSame(
            [
                'id' => 11,
            ],
            $noCriteriaBody->getContent()
        );
    }

    public function testSchemaLevelObjectExampleTakesPrecedence(): void
    {
        $api = (new OpenApiDefinitionLoader())->load(
            FixturesLocation::OPEN_API_WITH_SCHEMA_EXAMPLE
        );

        $operation = self::getOperation($api, 'createObjectBody');
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

        $operation = self::getOperation($api, 'createMediaTypeExamples');

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

        $operation = self::getOperation($api, 'createOptionalBodyWithoutExample');

        self::assertCount(0, $operation->getExamples());
    }

    private static function getOperation(Api $api, string $id): Operation
    {
        $operation = $api->getOperations()
            ->first(static fn (Operation $operation): bool => $operation->getId() === $id)
        ;

        self::assertInstanceOf(Operation::class, $operation);

        return $operation;
    }
}
