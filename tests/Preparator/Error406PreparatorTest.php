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
use APITester\Test\Preparator\Error406Preparator;
use APITester\Util\Assert;
use cebe\openapi\spec\Schema;

final class Error406PreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error406Preparator();
        $preparator->configure(
            [
                'mediaTypes' => [
                    'application/vnd.koan',
                    'application/javascript',
                    'application/json',
                ],
                'casesCount' => 2,
            ]
        );

        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['parent']
        );
    }

    public function testReusesOperationExample(): void
    {
        $api = Api::create()
            ->addOperation(
                Operation::create('test', '/test/{id}', 'POST')
                    ->addPathParameter(
                        Parameter::create('id')->setSchema(
                            new Schema([
                                'type' => 'integer',
                                'minimum' => 42,
                                'maximum' => 42,
                            ])
                        )
                    )
                    ->addRequestBody(Body::create(new Schema([
                        'type' => 'object',
                    ])))
                    ->addResponse(
                        DefinitionResponse::create(200)
                            ->setMediaType('application/json')
                    )
                    ->addExample(
                        OperationExample::create('default')
                            ->setPathParameter('id', '42')
                            ->setBodyContent([
                                'known' => 'value',
                            ])
                            ->setResponse(ResponseExample::create('200'))
                    )
            );

        $preparator = new Error406Preparator();
        $preparator->configure([
            'mediaTypes' => [
                'application/json',
                'application/vnd.api-tester.unsupported',
            ],
        ]);

        Assert::objectsEqual(
            [
                new TestCase(
                    Error406Preparator::getName() . ' - test - InvalidMediaType',
                    OperationExample::create('test')
                        ->setPath('/test/{id}')
                        ->setMethod('POST')
                        ->setPathParameter('id', '42')
                        ->setHeader('Accept', 'application/vnd.api-tester.unsupported')
                        ->setBodyContent([
                            'known' => 'value',
                        ])
                        ->setResponse(ResponseExample::create('406')),
                ),
            ],
            $preparator->doPrepare($api->getOperations()),
            ['parent']
        );
    }

    public function testSelectsMediaTypesDeterministically(): void
    {
        $api = Api::create()
            ->addOperation(
                Operation::create('test', '/test')
                    ->addResponse(
                        DefinitionResponse::create(200)
                            ->setMediaType('application/json')
                    )
            );

        $preparator = new Error406Preparator();
        $preparator->configure([
            'mediaTypes' => [
                'application/z-last',
                'application/json',
                'application/a-first',
                'application/middle',
            ],
            'casesCount' => 2,
        ]);

        for ($iteration = 0; $iteration < 5; ++$iteration) {
            $mediaTypes = [];
            foreach ($preparator->doPrepare($api->getOperations()) as $testCase) {
                $mediaTypes[] = $testCase->jsonSerialize()['request']->getHeaderLine('Accept');
            }

            self::assertSame(
                ['application/a-first', 'application/middle'],
                $mediaTypes
            );
        }
    }

    /**
     * @return iterable<int, array{Api, array<TestCase>}>
     */
    public static function getData(): iterable
    {
        yield [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )->addResponse(
                        DefinitionResponse::create(200)
                            ->setMediaType('application/json')
                            ->setBody(
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
                ),
            [
                new TestCase(
                    Error406Preparator::getName() . ' - test - InvalidMediaType',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setHeader('Accept', 'application/javascript')
                        ->setResponse(ResponseExample::create('406')),
                ),
                new TestCase(
                    Error406Preparator::getName() . ' - test - InvalidMediaType',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setHeader('Accept', 'application/vnd.koan')
                        ->setResponse(ResponseExample::create('406')),
                ),
            ],
        ];
    }
}
