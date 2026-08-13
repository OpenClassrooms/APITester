<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Schema\Entity\Api;
use APITester\Schema\Entity\Body;
use APITester\Schema\Entity\Example\BodyExample;
use APITester\Schema\Entity\Example\OperationExample;
use APITester\Schema\Entity\Example\ResponseExample;
use APITester\Schema\Entity\Operation;
use APITester\Schema\Entity\Parameter;
use APITester\Schema\Entity\Response as DefinitionResponse;
use APITester\Test\Entity\TestCase;
use APITester\Test\Preparator\Error404Preparator;
use APITester\Util\Assert;
use cebe\openapi\spec\Schema;

final class Error404PreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error404Preparator();

        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['parent', 'body']
        );
    }

    public function testReusesValidExampleData(): void
    {
        $api = Api::create()
            ->addOperation(
                Operation::create('updateTest', '/test/{id}', 'PUT')
                    ->addPathParameter(
                        Parameter::create('id')->setSchema(
                            new Schema([
                                'type' => 'integer',
                                'minimum' => 1,
                                'maximum' => 1,
                            ])
                        )
                    )
                    ->addQueryParameter(
                        Parameter::create('lang')->setSchema(
                            new Schema([
                                'type' => 'string',
                            ])
                        )
                    )
                    ->addQueryParameter(
                        Parameter::create('sort')->setSchema(
                            new Schema([
                                'type' => 'object',
                                'properties' => [
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ])
                        )
                    )
                    ->addRequestBody(
                        Body::create(
                            new Schema([
                                'type' => 'object',
                                'required' => ['name'],
                                'properties' => [
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ]),
                        )
                    )
                    ->addResponse(DefinitionResponse::create(200))
                    ->addResponse(
                        DefinitionResponse::create(404)
                            ->setDescription('description test')
                    )
                    ->addExample(
                        OperationExample::create('200')
                            ->setQueryParameter('lang', 'en')
                            ->setParameter(
                                'sort',
                                [
                                    'name' => 'asc',
                                ],
                                Parameter::TYPE_QUERY,
                                'object',
                                true
                            )
                            ->setBody(
                                BodyExample::create([
                                    'name' => 'John Doe',
                                ])
                            )
                            ->setResponse(ResponseExample::create('200'))
                    )
            );

        $expected = [
            new TestCase(
                Error404Preparator::getName() . ' - updateTest - RandomPath',
                OperationExample::create('test')
                    ->setPath('/test/{id}')
                    ->setMethod('PUT')
                    ->setPathParameter('id', '1')
                    ->setQueryParameter('lang', 'en')
                    ->setParameter(
                        'sort',
                        [
                            'name' => 'asc',
                        ],
                        Parameter::TYPE_QUERY,
                        'object',
                        true
                    )
                    ->setBodyContent([
                        'name' => 'John Doe',
                    ])
                    ->setResponse(ResponseExample::create('404', 'description test')),
            ),
        ];

        $preparator = new Error404Preparator();

        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['parent']
        );
    }

    public function testUsesAStableValueForTheLastResourcePathParameter(): void
    {
        $api = Api::create()
            ->addOperation(
                Operation::create('getChild', '/parents/{parentId}/children/{childId}/{view}')
                    ->addPathParameter(
                        Parameter::create('parentId')->setSchema(
                            new Schema([
                                'type' => 'integer',
                                'minimum' => 1,
                                'maximum' => 100,
                            ])
                        )
                    )
                    ->addPathParameter(
                        Parameter::create('childId')->setSchema(
                            new Schema([
                                'type' => 'string',
                                'maxLength' => 12,
                                'pattern' => '^[a-z0-9]{1,12}$',
                            ])
                        )
                    )
                    ->addPathParameter(
                        Parameter::create('view')->setSchema(
                            new Schema([
                                'type' => 'string',
                                'enum' => ['summary', 'details'],
                            ])
                        )
                    )
                    ->addResponse(DefinitionResponse::create(200))
                    ->addResponse(DefinitionResponse::create(404))
                    ->addExample(
                        OperationExample::create('default')
                            ->setPathParameter('parentId', '10')
                            ->setPathParameter('childId', 'existing')
                            ->setPathParameter('view', 'summary')
                            ->setResponse(ResponseExample::create('200'))
                    )
            );

        $preparator = new Error404Preparator();
        for ($iteration = 0; $iteration < 5; ++$iteration) {
            $testCases = [];
            foreach ($preparator->doPrepare($api->getOperations()) as $testCase) {
                $testCases[] = $testCase;
            }

            self::assertCount(1, $testCases);
            self::assertSame(
                '/parents/10/children/apitesternot/summary',
                $testCases[0]->jsonSerialize()['request']->getUri()->getPath()
            );
        }
    }

    public function testSkipsEnumOnlyPaths(): void
    {
        $api = Api::create()
            ->addOperation(
                Operation::create('getFacet', '/facets/{domain}/{facet}')
                    ->addPathParameter(
                        Parameter::create('domain')->setSchema(
                            new Schema([
                                'type' => 'string',
                                'enum' => ['organization', 'job-posting'],
                            ])
                        )
                    )
                    ->addPathParameter(
                        Parameter::create('facet')->setSchema(
                            new Schema([
                                'type' => 'string',
                                'enum' => ['provider', 'country'],
                            ])
                        )
                    )
                    ->addResponse(DefinitionResponse::create(200))
                    ->addResponse(DefinitionResponse::create(404))
            );

        $preparator = new Error404Preparator();

        self::assertCount(0, $preparator->doPrepare($api->getOperations()));
    }

    /**
     * @return iterable<array-key, array{Api, array<TestCase>}>
     */
    public static function getData(): iterable
    {
        yield 'with param' => [
            Api::create()
                ->addOperation(
                    Operation::create('getTest', '/test/{id}')
                        ->addPathParameter(
                            Parameter::create('id')->setSchema(
                                new Schema([
                                    'type' => 'integer',
                                    'minimum' => 1,
                                    'maximum' => 1,
                                ])
                            )
                        )
                        ->addResponse(DefinitionResponse::create(200))
                        ->addResponse(
                            DefinitionResponse::create(404)
                                ->setDescription('description test')
                        )
                ),
            [
                new TestCase(
                    Error404Preparator::getName() . ' - getTest - RandomPath',
                    OperationExample::create('test1')
                        ->setPath('/test/{id}')
                        ->setPathParameter('id', '1')
                        ->setResponse(ResponseExample::create('404', 'description test')),
                ),
            ],
        ];

        yield 'without param test is not created' => [
            Api::create()
                ->addOperation(
                    Operation::create('postTest', '/test', 'POST')
                        ->addRequestBody(
                            Body::create(
                                new Schema([
                                    'type' => 'object',
                                    'required' => ['name'],
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ]),
                            )
                        )
                        ->addResponse(DefinitionResponse::create(200))
                        ->addResponse(
                            DefinitionResponse::create(404)
                                ->setDescription('description test')
                        )
                ),
            [

            ],
        ];
    }
}
