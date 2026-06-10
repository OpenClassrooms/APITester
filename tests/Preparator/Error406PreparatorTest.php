<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Schema\Entity\Api;
use APITester\Schema\Entity\Example\OperationExample;
use APITester\Schema\Entity\Example\ResponseExample;
use APITester\Schema\Entity\Operation;
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
            ]
        );

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
