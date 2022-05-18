<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Preparator\Error406Preparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

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
            $preparator->doPrepare($api->getOperations())
        );
    }

    /**
     * @return iterable<int, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
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
                    new Request('GET', '/test', [
                        'Accept' => 'application/javascript',
                    ]),
                    new Response(406)
                ),
                new TestCase(
                    Error406Preparator::getName() . ' - test - InvalidMediaType',
                    new Request('GET', '/test', [
                        'Accept' => 'application/vnd.koan',
                    ]),
                    new Response(406)
                ),
            ],
        ];
    }
}
