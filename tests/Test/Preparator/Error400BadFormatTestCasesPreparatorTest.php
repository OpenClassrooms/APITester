<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\RequestExample;
use APITester\Preparator\Error400BadFormatTestCasesPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use APITester\Util\Json;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;

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
            $preparator->prepare($api->getOperations())
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
                    'test',
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
                            \APITester\Definition\Request::create(
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
                    'test',
                    new Request('GET', '/test', [], Json::encode([
                        'foo' => 'foo',
                    ])),
                    new Response(400)
                ),
            ],
        ];
    }
}
