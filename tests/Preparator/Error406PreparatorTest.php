<?php

declare(strict_types=1);

namespace APITester\Tests\Preparator;

use APITester\Definition\Api;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\Response as DefinitionResponse;
use APITester\Preparator\Error406Preparator;
use APITester\Test\TestCase;
use APITester\Util\Assert;
use cebe\openapi\spec\Schema;
use Psr\Log\AbstractLogger;

final class Error406PreparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param TestCase[] $expected
     */
    public function test(Api $api, array $expected): void
    {
        $preparator = self::createPreparator([
            'application/vnd.koan',
            'application/javascript',
            'application/json',
        ]);

        Assert::objectsEqual(
            $expected,
            $preparator->doPrepare($api->getOperations()),
            ['parent']
        );
    }

    public function testKeepsDocumentedRequestPathWhenChangingAccept(): void
    {
        $preparator = self::createPreparator(
            ['application/xml', 'application/json'],
            ['Accept' => 'application/json']
        );
        $api = Api::create()
            ->addOperation(
                Operation::create('courseIntroduction', '/courses/{courseId}/introduction')
                    ->addPathParameter(self::courseIdPathParameter())
                    ->addExample(
                        OperationExample::create('default')
                            ->setPathParameter('courseId', '1603881')
                    )
                    ->addResponse(
                        DefinitionResponse::create(200)
                            ->setMediaType('application/json')
                    )
            )
        ;

        $testCases = iterator_to_array($preparator->doPrepare($api->getOperations()));

        self::assertCount(1, $testCases);
        $request = $testCases[0]->jsonSerialize()['request'];
        self::assertSame('/courses/1603881/introduction', (string) $request->getUri());
        self::assertSame('application/xml', $request->getHeaderLine('Accept'));
    }

    public function testSkipsRequiredPathParameterWhenNoExampleExists(): void
    {
        $preparator = self::createPreparator(['application/xml', 'application/json']);
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
                Operation::create('courseIntroduction', '/courses/{courseId}/introduction')
                    ->addPathParameter(self::courseIdPathParameter())
                    ->addResponse(
                        DefinitionResponse::create(200)
                            ->setMediaType('application/json')
                    )
            )
        ;

        self::assertCount(0, iterator_to_array($preparator->doPrepare($api->getOperations())));
        self::assertSame(
            ['Skipping 406 test for operation courseIntroduction: required path parameter courseId has no example.'],
            $logger->messages
        );
    }

    /**
     * @param array<string>         $mediaTypes
     * @param array<string, string> $headers
     */
    private static function createPreparator(array $mediaTypes, array $headers = []): Error406Preparator
    {
        $preparator = new Error406Preparator();
        $preparator->configure([
            'mediaTypes' => $mediaTypes,
            'headers' => $headers,
        ]);

        return $preparator;
    }

    private static function courseIdPathParameter(): Parameter
    {
        return Parameter::create('courseId')->setSchema(
            new Schema([
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => 2147483647,
            ])
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
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setHeader('Accept', 'application/javascript')
                        ->setResponse(ResponseExample::create('406')),
                ),
                new TestCase(
                    Error406Preparator::getName() . ' - test - InvalidMediaType',
                    OperationExample::create('test')
                        ->setPath('/test')
                        ->setHeader('Accept', 'application/vnd.koan')
                        ->setResponse(ResponseExample::create('406')),
                ),
            ],
        ];
    }
}
