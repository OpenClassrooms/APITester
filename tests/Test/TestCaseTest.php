<?php

declare(strict_types=1);

namespace APITester\Tests\Test;

use APITester\Definition\Collection\Responses;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\Operation;
use APITester\Test\Exception\InvalidResponseSchemaException;
use APITester\Test\TestCase;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase as UnitTestCase;

class TestCaseTest extends UnitTestCase
{
    private ?TestCase $testCase;

    public function testGivenValidResponseRegardingSchemaAndShouldValidateSchemaResponseOptionIsDisabledWhenAssertThenNoErrorIsThrown(): void
    {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidSchema(),
            $this->getValidResponseContent(),
            false
        );

        try {
            $this->whenAssert();
        } catch (\Throwable $e) {
            static::fail('Should not raise an error');
        }
    }

    public function testGivenInvalidValidResponseRegardingSchemaAndShouldValidateSchemaResponseOptionIsDisabledWhenAssertThenNoErrorIsThrown(): void
    {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidSchema(),
            $this->getInvalidResponseContent(),
            false
        );

        try {
            $this->whenAssert();
        } catch (\Throwable $e) {
            static::fail('Should not raise an error');
        }
    }

    public function testGivenInvalidResponseRegardingSchemaAndShouldValidateSchemaResponseOptionIsEnabledWhenAssertThenAnErrorIsThrown(): void
    {
        $this->expectException(InvalidResponseSchemaException::class);

        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidSchema(),
            $this->getInvalidResponseContent()
        );

        $this->whenAssert();
    }

    public function testGivenSchemaResponseExistsButForDifferentStatusCodeAndShouldValidateSchemaResponseOptionIsEnabledWhenAssertThenNoErrorIsThrown(): void
    {
        $this->testCase = $this->givenTestCase(
            201,
            $this->getValidSchema(),
            $this->getValidResponseContent()
        );

        $this->whenAssert();
    }

    public function testGivenValidResponseRegardingSchemaAndShouldValidateSchemaResponseOptionIsEnabledWhenAssertThenNoErrorIsThrown(): void
    {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidSchema(),
            $this->getValidResponseContent()
        );

        try {
            $this->whenAssert();
        } catch (\Throwable $e) {
            static::fail('Should not raise an error');
        }
    }

    /**
     * Privates
     */

    private function getValidSchema(): Schema
    {
        return new Schema([
            'type' => 'object',
            'properties' => [
                'foo' => [
                    'type' => 'string',
                ],
            ],
        ]);
    }

    private function getValidResponseContent(): string
    {
        return '{ "foo" : "bar" }';
    }

    private function getInvalidResponseContent(): string
    {
        return '{ "foo" : 42 }';
    }

    private function givenTestCase(
        int $operationResponseStatusCode,
        Schema $operationResponseBody,
        string $responseContent,
        bool $shouldValidateResponseSchema = true
    ): TestCase
    {
        $testCase = new TestCase(
            'test1',
            OperationExample::create(
                'test1_example',
                Operation::create('test1', '/test1')
                    ->setResponses(
                        new Responses([
                            \APITester\Definition\Response::create($operationResponseStatusCode)
                                ->setMediaType('application/json')
                                ->setBody(
                                    $operationResponseBody
                                ),
                        ])
                    )
            ),
            ['body'],
            $shouldValidateResponseSchema
        );

        $testCase->setResponse(new Response(200, [], $responseContent));
        return $testCase;
    }

    private function whenAssert(): void
    {
        $this->testCase->assert();
    }
}