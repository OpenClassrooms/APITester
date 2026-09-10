<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Schema\Entity\Api;
use APITester\Schema\Entity\Body;
use APITester\Schema\Entity\Example\BodyExample;
use APITester\Schema\Entity\Example\OperationExample;
use APITester\Schema\Entity\Example\ResponseExample;
use APITester\Schema\Entity\Operation;
use APITester\Schema\Entity\Parameter;
use APITester\Test\Entity\TestCase;
use APITester\Test\Preparator\Error400BadFormatsPreparator;
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
    public static function getData(): iterable
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
