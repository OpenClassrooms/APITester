<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Body;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Preparator\Error404Preparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use cebe\openapi\spec\Schema;
use Psr\Log\AbstractLogger;

final class Error404PreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = new Error404Preparator();

        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['parent', 'body']
        );
    }

    public function testKeepsDocumentedRequestBodyWhenRandomizingPath(): void
    {
        $preparator = new Error404Preparator();
        $api = Api::create()
            ->addOperation(
                Operation::create('patchTest', '/test/{id}', 'PATCH')
                    ->addPathParameter(self::integerIdPathParameter())
                    ->addRequestBody(self::nameBody(required: true))
                    ->addExample(
                        OperationExample::create('default')
                            ->setPathParameter('id', '1')
                            ->setBodyContent(['name' => 'valid'])
                    )
                    ->addResponse(DefinitionResponse::create(200))
                    ->addResponse(
                        DefinitionResponse::create(404)
                            ->setDescription('description test')
                    )
            )
        ;

        $testCases = iterator_to_array($preparator->doPrepare($api->getOperations()));

        self::assertCount(1, $testCases);
        $request = $testCases[0]->jsonSerialize()['request'];
        self::assertSame('PATCH', $request->getMethod());
        self::assertSame('/test/1', (string) $request->getUri());
        self::assertSame('{"name":"valid"}', (string) $request->getBody());
    }

    public function testSkipsRequiredRequestBodyWhenNoExampleExists(): void
    {
        $preparator = new Error404Preparator();
        $logger = new class() extends AbstractLogger {
            /**
             * @var array<string>
             */
            public array $messages = [];

            /**
             * @param array<string, mixed> $context
             */
            public function log($level, $message, array $context = []): void
            {
                $this->messages[] = (string) $message;
            }
        };
        $preparator->setLogger($logger);
        $api = Api::create()
            ->addOperation(
                Operation::create('patchTest', '/test/{id}', 'PATCH')
                    ->addPathParameter(self::integerIdPathParameter())
                    ->addRequestBody(self::nameBody(required: true))
                    ->addResponse(DefinitionResponse::create(200))
                    ->addResponse(DefinitionResponse::create(404))
            )
        ;

        self::assertCount(0, iterator_to_array($preparator->doPrepare($api->getOperations())));
        self::assertSame(
            ['Skipping 404 test for operation patchTest: required request body has no example.'],
            $logger->messages
        );
    }

    private static function integerIdPathParameter(): Parameter
    {
        return Parameter::create('id')->setSchema(
            new Schema([
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => 1,
            ])
        );
    }

    private static function nameBody(bool $required = false): Body
    {
        $body = Body::create(
            new Schema([
                'type' => 'object',
                'required' => ['name'],
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
            ])
        );

        return $required ? $body->setRequired() : $body;
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
                        ->addPathParameter(self::integerIdPathParameter())
                        ->addResponse(DefinitionResponse::create(200))
                        ->addResponse(
                            DefinitionResponse::create(404)
                                ->setDescription('description test')
                        )
                ),
            [
                new TestCase(
                    Error404Preparator::getName() . ' - getTest - RandomPath',
                    OperationExample::create('test1')
                        ->setPath('/test/{id}')
                        ->setPathParameter('id', '1')
                        ->setResponse(ResponseExample::create('404', 'description test')),
                ),
            ],
        ];

        yield 'without param' => [
            Api::create()
                ->addOperation(
                    Operation::create('postTest', '/test', 'POST')
                        ->addRequestBody(self::nameBody())
                        ->addResponse(DefinitionResponse::create(200))
                        ->addResponse(
                            DefinitionResponse::create(404)
                                ->setDescription('description test')
                        )
                ),
            [
                new TestCase(
                    Error404Preparator::getName() . ' - postTest - RandomPath',
                    OperationExample::create('test1')
                        ->setPath('/test')
                        ->setMethod('POST')
                        ->setBodyContent(
                            [
                                'name' => 'toto',
                            ]
                        )
                        ->setResponse(
                            ResponseExample::create('404', 'description test')
                        ),
                ),
            ],
        ];
    }
}
