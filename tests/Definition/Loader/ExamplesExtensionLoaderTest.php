<?php

declare(strict_types=1);

namespace APITester\Tests\Definition\Loader;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Example\BodyExample;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Example\ResponseExample;
use APITester\Definition\Loader\ExamplesExtensionLoader;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\Response;
use APITester\Tests\Fixtures\FixturesLocation;
use APITester\Util\Assert;
use PHPUnit\Framework\TestCase;

final class ExamplesExtensionLoaderTest extends TestCase
{
    /**
     * @dataProvider getLoadAndAppendData
     */
    public function testLoadAndAppend(string $path, Operations $operations, Operations $expected): void
    {
        $operations = ExamplesExtensionLoader::load(
            $path,
            $operations
        );

        Assert::objectsEqual($expected, $operations);
    }

    /**
     * @return iterable<array{0: string, 1: Operations, 2: Operations}>
     */
    public function getLoadAndAppendData(): iterable
    {
        yield 'Nominal case' => [
            FixturesLocation::CONFIG_EXAMPLES_EXTENSION,
            new Operations([
                Operation::create('listPets', '/pets')
                    ->addPathParameter(Parameter::create('id'))
                    ->addQueryParameter(Parameter::create('type'))
                    ->addResponse(Response::create(200))
                    ->addResponse(Response::create(400)->setMediaType('application/json')),
            ]),
            new Operations([
                Operation::create('listPets', '/pets')
                    ->addPathParameter(Parameter::create('id'))
                    ->addQueryParameter(Parameter::create('type'))
                    ->addResponse(Response::create(200))
                    ->addResponse(Response::create(400)->setMediaType('application/json'))
                    ->addExample(
                        OperationExample::create('listPets400')
                            ->setPathParameters([
                                'id' => 123,
                            ])
                            ->setQueryParameters([
                                'id' => 123,
                            ])
                            ->setHeaders([
                                'id' => 123,
                            ])
                            ->setBody(
                                BodyExample::create([
                                    'id' => 123,
                                ])
                            )
                            ->setResponse(
                                ResponseExample::create(null, [
                                    'message' => 'Bad request',
                                ])
                                    ->setStatusCode('400')
                                    ->setHeaders([
                                        'id' => 123,
                                    ])
                            )
                    )
                    ->addExample(
                        OperationExample::create('listPets200')
                            ->setResponse(new ResponseExample())
                    ),
            ]),
        ];
    }
}
