<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Preparator;

use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\RequestExample;
use OpenAPITesting\Preparator\Error400BadFormatTestCasesPreparator;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Assert;
use OpenAPITesting\Util\Json;

final class Error400BadFormatTestCasesPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error400BadFormatTestCasesPreparator();
        Assert::objectsEqual(
            $expected,
            $preparator->prepare($api->getOperations()),
            ['size', 'id', 'groups']
        );
    }

    /**
     * @return iterable<string, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
        yield 'For email format in query param' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->setMethod('GET')
                        ->addQueryParameter(
                            (new Parameter(
                                'foo_query',
                                true,
                                new Schema([
                                    'type' => 'string',
                                    'format' => 'email',
                                ])
                            ))
                        )
                ),
            [
                new TestCase(
                    'foo_query_param_bad_format_test',
                    new Request('GET', '/test?foo_query=foo'),
                    new Response(400)
                ),
            ],
        ];

        yield 'For email format in body' => [
            Api::create()
                ->addOperation(
                    Operation::create(
                        'test',
                        '/test'
                    )
                        ->setMethod('GET')
                        ->addRequest(
                            \OpenAPITesting\Definition\Request::create(
                                'application/json',
                                new Schema([
                                    'type' => 'object',
                                    'properties' => [
                                        'foo' => [
                                            'type' => 'string',
                                            'format' => 'email',
                                        ],
                                    ],
                                    'required' => ['foo'],
                                ])
                            )->addExample(new RequestExample('foo', 'foo@bar.com'))
                        )
                ),
            [
                new TestCase(
                    'foo_body_field_bad_format_test',
                    new Request('GET', '/test', [], Json::encode([
                        'foo' => 'foo',
                    ])),
                    new Response(400)
                ),
            ],
        ];
    }
}
