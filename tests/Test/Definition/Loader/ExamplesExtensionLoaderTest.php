<?php

declare(strict_types=1);

namespace APITester\Tests\Test\Definition\Loader;

use APITester\Definition\Collection\Operations;
use APITester\Definition\Loader\ExamplesExtensionLoader;
use APITester\Definition\Operation;
use APITester\Definition\Parameter;
use APITester\Definition\ParameterExample;
use APITester\Definition\Response;
use APITester\Definition\ResponseExample;
use APITester\Util\Assert;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-type FixtureFormat array{
 *      operationId: string,
 *      request: array{
 *          path?: array<string, string>,
 *          query?: array<string, string>,
 *          header?: array<string, string>,
 *          body?: array{
 *              mediaType: string,
 *              content: array<array-key, mixed>
 *          }
 *      },
 *      response: array{
 *          statusCode: int,
 *          header?: array<string, string>,
 *          body?: array{
 *              mediaType: string,
 *              content: array<array-key, mixed>
 *          }
 *      }
 * }
 */
final class ExamplesExtensionLoaderTest extends TestCase
{
    /**
     * @dataProvider getLoadAndAppendData
     *
     * @param array<string, FixtureFormat> $data
     */
    public function testLoadAndAppend(array $data, Operations $operations, Operations $expected): void
    {
        $operations = ExamplesExtensionLoader::load($data, $operations);

        Assert::objectsEqual($expected, $operations);
    }

    /**
     * @return iterable<string, array{array<string, FixtureFormat>, Operations, Operations}>
     */
    public function getLoadAndAppendData(): iterable
    {
        yield 'Nominal case' => [
            [
                'listPets400' => [
                    'operationId' => 'listPets',
                    'request' => [
                        'parameters' => [
                            'query' => [
                                'type' => 'Horse',
                            ],
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
                    ->addResponse(Response::create(200))
                    ->addResponse(Response::create(400)->setMediaType('application/json')),
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
                            ->setMediaType('application/json')
                            ->addExample(
                                new ResponseExample('listPets400', [
                                    'message' => 'Bad request',
                                ])
                            )
                    ),
            ]),
        ];
    }
}