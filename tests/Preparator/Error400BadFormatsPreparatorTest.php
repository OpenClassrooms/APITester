<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Body;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Preparator\Error400BadFormatsPreparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;

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
            $preparator->doPrepare($api->getOperations()),
            ['parent'],
        );
    }

    /**
     * @return iterable<string, array{Api, array<TestCase>}>
     */
    public function getData(): iterable
    {
        yield 'For email format in query param' => [
            Api::create()->addOperation(
                Operation::create('test', '/test')
                    ->addQueryParameter(
                        new Parameter(
                            'foo_query',
                            true,
                            [
                                'type' => 'string',
                                'format' => 'email',
                            ]
                        )
                    )
            ),
            [
                new TestCase(
                    Error400BadFormatsPreparator::getName() . ' - test - foo_query_param_bad_email_format',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setQueryParameter('foo_query', 'foo')
                        ->setResponse(ResponseExample::create('400'))
                ),
            ],
        ];

        yield 'For email format in body' => [
            Api::create()->addOperation(
                Operation::create('test', '/test')->addRequestBody(
                    Body::create(
                        [
                            'type' => 'object',
                            'properties' => [
                                'foo' => [
                                    'type' => 'string',
                                    'format' => 'email',
                                ],
                            ],
                            'required' => ['foo'],
                        ],
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
                    Error400BadFormatsPreparator::getName() . ' - test - foo_body_field_bad_format_test',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setBodyContent(
                            [
                                'foo' => 'foo',
                            ]
                        )
                        ->setResponse(ResponseExample::create('400'))
                ),
            ],
        ];
    }
}
