<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\Request as DefinitionRequest;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Preparator\Error404TestCasesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use APITester\Util\Json;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error404TestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error404TestCasesPreparator();

        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api->getOperations()),
            ['body']
        );
    }

    /**
     * @return iterable<array-key, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
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
                    'getTest',
                    new Request('GET', '/test/1'),
                    new Response(404, [], 'description test')
                ),
            ],
        ];

        yield 'without param' => [
            Api::create()
                ->addOperation(
                    Operation::create('postTest', '/test', 'POST')
                        ->addRequest(
                            DefinitionRequest::create(
                                'application/json',
                                new Schema([
                                    'type' => 'object',
                                    'required' => ['name'],
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                        ],
                                    ],
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
                    'postTest',
                    new Request(
                        'POST',
                        '/test',
                        [
                            'content-type' => 'application/json',
                        ],
                        Json::encode([
                            'name' => 'aaa',
                        ])
                    ),
                    new Response(404, [], 'description test')
                ),
            ],
        ];
    }
}
