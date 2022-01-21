<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Preparator\Error416TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;

final class Error416TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(array $config, Api $api, array $expected): void
    {
        $preparator = new Error416TestCasesPreparator();

        $preparator->configure($config);

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api),
            ['size', 'id', 'headerNames', 'groups', 'headers', 'name']
        );
    }

    /**
     * @return iterable<int, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
        yield [
            [
                'range' => [
                    [
                        'in' => 'header',
                        'name' => 'Range',
                        'unit' => 'items',
                    ],
                    [
                        'in' => 'query',
                        'name' => [
                            'lower' => 'offset',
                            'upper' => 'limit',
                        ],
                    ],
                ],
            ],
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )->addResponse(
                        DefinitionResponse::create()
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
                    'test',
                    new Request('GET', '/test', [
                        'Accept' => 'test',
                    ]),
                    new Response(416)
                ),
            ],
        ];
    }
}
