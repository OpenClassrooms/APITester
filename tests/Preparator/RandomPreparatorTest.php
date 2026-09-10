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
use APITester\Test\Preparator\RandomPreparator;
use APITester\Util\Assert;
use cebe\openapi\spec\Schema;

final class RandomPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new RandomPreparator();
        $preparator->configure([
            'casesCount' => 2,
            'response' => [
                'statusCode' => '#^(?!500)\d+#',
            ],
        ]);

        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['parent']
        );
    }

    /**
     * @return iterable<int, array{Api, array<TestCase>}>
     */
    public static function getData(): iterable
    {
        yield [
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
                        ->addRequestBody(
                            Body::create([
                                'type' => 'object',
                                'required' => ['name'],
                                'properties' => [
                                    'name' => [
                                        'type' => 'string',
                                        'enum' => ['John'],
                                    ],
                                ],
                            ])
                        )
                        ->addResponse(DefinitionResponse::create(200))
                        ->addResponse(
                            DefinitionResponse::create(404)
                                ->setDescription('description test')
                        )
                ),
            [
                new TestCase(
                    RandomPreparator::getName() . ' - getTest - _random',
                    OperationExample::create('test1')
                        ->setPath('/test/{id}')
                        ->setPathParameter('id', '1')
                        ->setBodyContent([
                            'name' => 'John',
                        ])
                        ->setResponse(
                            ResponseExample::create('#^(?!500)\d+#')
                        ),
                ),
            ],
        ];
    }
}
