<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\Request as DefinitionRequest;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Preparator\Error404TestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;

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
            ['size', 'id', 'headerNames', 'groups', 'stream', 'excludedFields']
        );
    }

    /**
     * @return iterable<array-key, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
        yield [
            Api::create()->addOperation(
                Operation::create('getTest', '/test/{id}')
                    ->addPathParameter(Parameter::create('id'))
                    ->addResponse(DefinitionResponse::create(200))
                    ->addResponse(
                        DefinitionResponse::create(404)
                            ->setDescription('description test')
                    )
            ),
            [
                new TestCase(
                    'getTest_404',
                    new Request('GET', '/test/-9999'),
                    new Response(404, [], 'description test')
                ),
            ],
        ];

        yield [
            Api::create()->addOperation(
                Operation::create('postTest', '/test')
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
                    'postTest_404',
                    new Request(
                        'POST',
                        '/test',
                        [],
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
