<?php

declare(strict_types=1);

namespace OpenAPITesting\Tests\Test\Definition\Loader;

use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Loader\Exception\InvalidExampleFixturesException;
use OpenAPITesting\Definition\Loader\FixturesLoader;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Parameter;
use OpenAPITesting\Definition\ParameterExample;
use OpenAPITesting\Definition\Response;
use OpenAPITesting\Definition\ResponseExample;
use OpenAPITesting\Util\Assert;
use PHPUnit\Framework\TestCase;

final class FixturesLoaderTest extends TestCase
{
    /**
     * @dataProvider getLoadAndAppendData
     *
     * @param array<array-key, mixed> $data
     *
     * @throws InvalidExampleFixturesException
     */
    public function testLoadAndAppend(array $data, Operations $operations, Operations $expected): void
    {
        $operations = (new FixturesLoader())->load($data)
            ->append($operations)
        ;

        Assert::objectsEqual($expected, $operations);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>,ExampleFixtures}>
     */
    public function getLoadAndAppendData(): iterable
    {
        yield 'Nominal case' => [
            [
                'listPets400' => [
                    'operationId' => 'listPets',
                    'request' => [
                        'query' => [
                            'type' => 'Horse',
                        ],
                    ],
                    'response' => [
                        'statusCode' => 400,
                        'body' => [
                            'mediaType' => 'application/json',
                            'content' => [
                                'message' => 'Bad request',
                            ],
                        ],
                    ],
                ],
            ],
            new Operations([
                Operation::create('listPets', '/pets')
                    ->addPathParameter(Parameter::create('id'))
                    ->addQueryParameter(
                        Parameter::create('type')
                            ->addExample(new ParameterExample('200', 'Dog'))
                    )
                    ->addResponse(Response::create(200)),
            ]),
            new Operations([
                Operation::create('listPets', '/pets')
                    ->addPathParameter(Parameter::create('id'))
                    ->addQueryParameter(
                        Parameter::create('type')
                            ->addExample(new ParameterExample('200', 'Dog'))
                            ->addExample(new ParameterExample('listPets400', 'Horse'))
                    )
                    ->addResponse(Response::create(200))
                    ->addResponse(
                        Response::create(400)
                            ->addExample(new ResponseExample('listPets400', [
                                'message' => 'Bad request',
                            ]))
                    ),
            ]),
        ];
    }
}
