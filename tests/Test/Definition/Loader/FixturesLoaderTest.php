<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Definition\Loader;

use OpenAPITesting\Definition\Collection\ExampleFixtures;
use OpenAPITesting\Definition\ExampleFixture;
use OpenAPITesting\Definition\Loader\Exception\InvalidExampleFixturesException;
use OpenAPITesting\Definition\Loader\FixturesLoader;
use OpenAPITesting\Util\Assert;
use PHPUnit\Framework\TestCase;

final class FixturesLoaderTest extends TestCase
{
    /**
     * @dataProvider getLoadFromPathData
     *
     * @param array<array-key, mixed> $data
     *
     * @throws InvalidExampleFixturesException
     */
    public function testLoadFromPath(array $data, ExampleFixtures $expected): void
    {
        $loader = (new FixturesLoader())->load($data);

        Assert::objectsEqual($loader->getExamples(), $expected);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>,ExampleFixtures}>
     */
    public function getLoadFromPathData(): iterable
    {
        yield 'basic example' => [
            [
                [
                    'operationId' => 'listPets',
                    'parameters' => [
                        'type' => 'Dog',
                    ],
                    'requestBody' => null,
                    'statusCode' => 400,
                    'responseBody' => null,
                ],
            ],
            new ExampleFixtures([
                (new ExampleFixture())
                    ->setOperationId('listPets')
                    ->setParameters([
                        'type' => 'Dog',
                    ])
                    ->setStatusCode(400),
            ]),
        ];
    }
}
