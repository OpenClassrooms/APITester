<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Body;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Error400BadFormatsPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use APITester\Util\Json;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

final class Error400BadFormatsPreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error400BadFormatsPreparator();
        Assert::objectsEqual(
            $expected,
            $preparator->getTestCases($api->getOperations())
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
                    'test/foo_query_param_bad_email_format',
                    new Request(
                        'GET',
                        '/test?foo_query=foo',
                    ),
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
                        ->addRequestBody(
                            Body::create(
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
                            )
                        )->addExample(
                            OperationExample::create('foo')
                                ->setBody(
                                    BodyExample::create([
                                        'foo' => 'foo@bar.com',
                                    ])
                                )
                        )
                ),
            [
                new TestCase(
                    'test/foo_body_field_bad_format_test',
                    new Request(
                        'GET',
                        '/test',
                        [
                            'content-type' => 'application/json',
                        ],
                        Json::encode([
                            'foo' => 'foo',
                        ])
                    ),
                    new Response(400)
                ),
            ],
        ];
    }
}
