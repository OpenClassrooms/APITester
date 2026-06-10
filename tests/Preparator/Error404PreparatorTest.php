<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Schema\Entity\Api;
use APITester\Schema\Entity\Body;
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

        yield 'without param' => [
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
                new TestCase(
                    Error404Preparator::getName() . ' - postTest - RandomPath',
                    OperationExample::create('test1')
                        ->setPath('/test')
                        ->setMethod('POST')
                        ->setBodyContent(
                            [
                                'name' => 'toto',
                            ]
                        )
                        ->setResponse(
                            ResponseExample::create('404', 'description test')
                        ),
                ),
            ],
        ];
    }
}
