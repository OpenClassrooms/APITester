<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Unit\src\Loader\Fixture;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use OpenAPITesting\Fixture\OpenApiTestSuiteFixture;
use OpenAPITesting\Fixture\OperationTestCaseFixture;
use OpenAPITesting\Loader\Fixture\OpenApiExampleFixtureLoader;
use OpenAPITesting\Loader\OpenApiLoader;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;
use PHPUnit\Framework\TestCase;

final class OpenApiExampleFixtureLoaderTest extends TestCase
{
    public const OPENAPI_LOCATION = __DIR__ . '/../../../fixtures/openapi.yaml';

    /**
     * @dataProvider getData
     *
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
     * @throws \cebe\openapi\exceptions\IOException
     * @throws \cebe\openapi\exceptions\TypeErrorException
     */
    public function testInvoke(OpenApiTestSuiteFixture $expectedFixture): void
    {
        $openApi = (new OpenApiLoader())(self::OPENAPI_LOCATION);
        $fixture = (new OpenApiExampleFixtureLoader())($openApi);

        Assert::assertObjectsEqualRejects(
            $expectedFixture->getOperationTestCaseFixtures(),
            $fixture->getOperationTestCaseFixtures(),
            ['size']
        );
    }

    /**
     * @return array<array<OpenApiTestSuiteFixture>>
     */
    public function getData(): array
    {
        return [
            [
                $this->buildTestSuiteFixture([
                    [
                        'request' => [
                            'path' => '/pets?1=1&kind=cat&limit=10',
                            'method' => 'GET',
                        ],
                        'response' => [
                            'statusCode' => 200,
                            'body' => [],
                        ],
                        'operationId' => 'listPets',
                        'description' => '200.default',
                    ],
                    [
                        'request' => [
                            'path' => '/pets?1=1&kind=horse&limit=aaa',
                            'method' => 'GET',
                        ],
                        'response' => [
                            'statusCode' => 400, // @todo: fix bad response mapping
                        ],
                        'operationId' => 'listPets',
                        'description' => 'default.badRequest',
                    ],
                    [
                        'request' => [
                            'path' => '/pets?1=1&limit=20',
                            'method' => 'GET',
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                        'operationId' => 'listPets',
                        'description' => '200.double',
                    ],
                    [
                        'request' => [
                            'path' => '/pets?1=1',
                            'method' => 'POST',
                            'body' => [
                                'id' => 10,
                                'name' => 'Jessica Smith',
                            ],
                        ],
                        'response' => [
                            'statusCode' => 201,
                        ],
                        'operationId' => 'createPets',
                        'description' => '201',
                    ],
                    [
                        'request' => [
                            'path' => '/pets?1=1',
                            'method' => 'POST',
                            'body' => [
                                'id' => 11,
                                'name' => 123,
                            ],
                        ],
                        'response' => [
                            'statusCode' => 400,
                        ],
                        'operationId' => 'createPets',
                        'description' => 'default.badRequest',
                    ],
                    [
                        'request' => [
                            'path' => '/pets/123?1=1',
                            'method' => 'GET',
                        ],
                        'response' => [
                            'statusCode' => 200,
                            'body' => [
                                'id' => 10,
                                'name' => 'Jessica Smith',
                            ],
                        ],
                        'operationId' => 'showPetById',
                        'description' => '200.200',
                    ],
                ]),
            ],
        ];
    }

    /**
     * @param array<array{'operationId': string|null, 'description': string|null, 'request': array{'method': string, 'path': string, 'headers'?: array<string, string>, 'body'?: array<mixed>}, 'response': array{'statusCode': int, 'headers'?: array<string, string>, 'body'?: array<mixed>}}> $fixturesData
     */
    private function buildTestSuiteFixture(array $fixturesData): OpenApiTestSuiteFixture
    {
        $testSuiteFixture = new OpenApiTestSuiteFixture();

        foreach ($fixturesData as $data) {
            $testSuiteFixture->add(
                new OperationTestCaseFixture(
                    $data['operationId'],
                    new Request(
                        $data['request']['method'],
                        new Uri($data['request']['path']),
                        $data['request']['headers'] ?? [],
                        isset($data['request']['body']) ? Json::encode($data['request']['body']) : null
                    ),
                    new Response(
                        $data['response']['statusCode'],
                        $data['response']['headers'] ?? [],
                        isset($data['response']['body']) ? Json::encode($data['response']['body']) : null
                    ),
                    $data['description']
                )
            );
        }

        return $testSuiteFixture;
    }
}
