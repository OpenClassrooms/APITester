<?php

declare(strict_types=1);

namespace APITester\Tests\Test;

use APITester\Definition\Collection\Responses;
use APITester\Definition\Example\OperationExample;
use APITester\Definition\OpenApiSpecification;
use APITester\Definition\Operation;
use APITester\Test\Exception\InvalidResponseSchemaException;
use APITester\Test\TestCase;
use APITester\Tests\Fixtures\FixturesLocation;
use cebe\openapi\Reader;
use cebe\openapi\spec\Schema;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase as UnitTestCase;

final class TestCaseTest extends UnitTestCase
{
    private TestCase $testCase;

    public function testValidSchemaWithoutValidationShouldPass(): void
    {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidResponseContent(),
            false
        );

        $this->testCase->assert();
    }

    public function testInvalidSchemaWithoutValidationShouldPass(): void
    {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getInvalidResponseContent(),
            false
        );

        $this->testCase->assert();
    }

    public function testInvalidSchemaWithValidationShouldFail(): void
    {
        $this->expectException(InvalidResponseSchemaException::class);

        $this->testCase = $this->givenTestCase(
            200,
            $this->getInvalidResponseContent()
        );

        $this->testCase->assert();
    }

    public function testNullablePropertiesWithSchemaValidationShouldPass(): void
    {
        $this->testCase = $this->givenTestCase(
            200,
            '{ "name" : "Rex", "id" : 1, "tag" : null }'
        );

        $this->testCase->assert();
    }

    public function testInvalidSubObjectShouldFail(): void
    {
        $this->expectException(InvalidResponseSchemaException::class);
        $this->expectExceptionMessage('Invalid field: location.city. Keyword validation failed: Value cannot be null');
        $this->testCase = $this->givenTestCase(
            200,
            '{ "name" : "Rex", "id" : 1, "tag" : null, "location" : { "city" : null, "country" : "France" } }'
        );

        $this->testCase->assert();
    }

    public function testWrongEnum(): void
    {
        $this->expectException(InvalidResponseSchemaException::class);
        $this->expectExceptionMessage(
            'Invalid field: location.city. Keyword validation failed: Value must be present in the enum'
        );
        $this->testCase = $this->givenTestCase(
            200,
            '{ "name" : "Rex", "id" : 1, "tag" : null, "location" : { "city" : "Montreuil", "country" : "France" } }'
        );

        $this->testCase->assert();
    }

    public function testNullRequiredPropertyWithSchemaValidationShouldFail(): void
    {
        $this->expectException(InvalidResponseSchemaException::class);

        $this->testCase = $this->givenTestCase(
            200,
            '{ "name" : 1, "id" : 1, "tag" : "vaccinated" }'
        );

        $this->testCase->assert();
    }

    public function testGivenSchemaResponseExistsButForDifferentStatusCodeAndShouldValidateSchemaResponseOptionIsEnabledWhenAssertThenNoErrorIsThrown(
    ): void {
        $this->testCase = $this->givenTestCase(
            201,
            $this->getValidResponseContent()
        );

        $this->testCase->assert();
    }

    public function testGivenValidResponseRegardingSchemaAndShouldValidateSchemaResponseOptionIsEnabledWhenAssertThenNoErrorIsThrown(
    ): void {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidResponseContent()
        );

        $this->testCase->assert();
    }

    public function testValidContentWithoutSpecificationJsonSchemaValidatorShouldPass(): void
    {
        $this->testCase = $this->givenTestCase(
            200,
            $this->getValidResponseContent(),
            true,
            false
        );
        $this->testCase->assert();
    }

    public function testInvalidContentWithoutSpecificationJsonSchemaValidatorShouldFail(): void
    {
        $this->expectException(InvalidResponseSchemaException::class);
        $this->testCase = $this->givenTestCase(
            200,
            $this->getInvalidResponseContent(),
            true,
            false
        );
        $this->testCase->assert();
    }

    private function getValidSchema(): Schema
    {
        return new Schema([
            'type' => 'object',
            'required' => ['id', 'name'],
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'format' => 'int64',
                ],
                'name' => [
                    'type' => 'string',
                ],
                'tag' => [
                    'type' => 'string',
                    'nullable' => true,
                ],
                'location' => [
                    'type' => 'object',
                    'required' => ['city', 'country'],
                    'properties' => [
                        'city' => [
                            'type' => 'string',
                            'enum' => ['Paris', 'Lyon', 'Marseille'],
                        ],
                        'country' => [
                            'type' => 'string',
                            'enum' => ['France'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function getValidResponseContent(): string
    {
        return '{ "name" : "Rex", "id" : 1, "tag" : "vaccinated", "location" : { "city" : "Paris", "country" : "France" }}';
    }

    private function getInvalidResponseContent(): string
    {
        return '{ "name" : 42, "id" : "1", "tag" : 1, "location" : { "city" : null, "country" : null }}';
    }

    private function givenTestCase(
        int $operationResponseStatusCode,
        string $responseContent,
        bool $shouldValidateResponseSchema = true,
        bool $openApiSpecification = true
    ): TestCase {
        $testCase = new TestCase(
            'test1',
            OperationExample::create(
                name: 'test1_example',
                operation: Operation::create('showPetById', '/pets/{petId}')
                    ->setResponses(
                        new Responses([
                            \APITester\Definition\Response::create($operationResponseStatusCode)
                                ->setMediaType('application/json')
                                ->setBody($this->getValidSchema()),
                        ])
                    ),
                statusCode: 200
            ),
            ['body'],
            $shouldValidateResponseSchema
        );

        if ($openApiSpecification) {
            $openApi = Reader::readFromYamlFile(FixturesLocation::OPEN_API_WITH_EXAMPLES);
            $testCase->setSpecification(new OpenApiSpecification($openApi));
        }
        $testCase->setResponse(new Response(200, [
            'Content-Type' => 'application/json',
        ], $responseContent));

        return $testCase;
    }
}
