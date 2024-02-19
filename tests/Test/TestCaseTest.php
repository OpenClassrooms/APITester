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

final class TestCaseTest extends UnitTestCase
{
    private ?TestCase $testCase;

    public function testGivenValidResponseRegardingSchemaAndShouldValidateSchemaResponseOptionIsDisabledWhenAssertThenNoErrorIsThrown(
    ): void {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidSchema(),
            $this->getValidResponseContent(),
            false
        );

        $this->whenAssert();
    }

    public function testGivenInvalidValidResponseRegardingSchemaAndShouldValidateSchemaResponseOptionIsDisabledWhenAssertThenNoErrorIsThrown(
    ): void {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidSchema(),
            $this->getInvalidResponseContent(),
            false
        );

        $this->whenAssert();
    }

    public function testGivenInvalidResponseRegardingSchemaAndShouldValidateSchemaResponseOptionIsEnabledWhenAssertThenAnErrorIsThrown(
    ): void {
        $this->expectException(InvalidResponseSchemaException::class);

        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidSchema(),
            $this->getInvalidResponseContent()
        );

        $this->whenAssert();
    }

    public function testGivenSchemaResponseExistsButForDifferentStatusCodeAndShouldValidateSchemaResponseOptionIsEnabledWhenAssertThenNoErrorIsThrown(
    ): void {
        $this->testCase = $this->givenTestCase(
            201,
            $this->getValidSchema(),
            $this->getValidResponseContent()
        );

        $this->whenAssert();
    }

    public function testGivenValidResponseRegardingSchemaAndShouldValidateSchemaResponseOptionIsEnabledWhenAssertThenNoErrorIsThrown(
    ): void {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidSchema(),
            $this->getValidResponseContent()
        );

        $this->whenAssert();
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
    ): TestCase {
        $testCase = new TestCase(
            'test1',
            OperationExample::create(
                name: 'test1_example',
                operation: Operation::create('test1', '/test1')
                    ->setResponses(
                        new Responses([
                            \APITester\Definition\Response::create($operationResponseStatusCode)
                                ->setMediaType('application/json')
                                ->setBody(
                                    $operationResponseBody
                                ),
                        ])
                    ),
                statusCode: 200
            ),
            ['body'],
            $shouldValidateResponseSchema
        );

        $testCase->setResponse(new Response(200, [], $responseContent));

        return $testCase;
    }

    private function whenAssert(): void
    {
        $this->testCase?->assert();
    }
}
