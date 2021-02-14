<?php

namespace OpenAPITesting\Tests\Models\Test;

use Generator;
use Nyholm\Psr7\Request;
use OpenAPITesting\Models\Test\InvalidStatusException;
use OpenAPITesting\Models\Test\OperationTestCase;
use OpenAPITesting\Tests\AssertTrait;
use OpenAPITesting\Tests\Fixtures\Models\OpenAPI\OperationStubUpdatePet;
use OpenAPITesting\Tests\Fixtures\Models\Test\OperationTestCaseStubUpdatePet1;
use OpenAPITesting\Tests\Fixtures\ResponseMock;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OperationTestCaseTest extends TestCase
{
    use AssertTrait;

    public static function getOperationTestCasesToLaunch(): Generator
    {
        yield '' =>
        [
            new OperationTestCaseStubUpdatePet1(),
            [new Request(OperationStubUpdatePet::METHOD, OperationStubUpdatePet::PATH)]
        ];
    }

    public static function getOperationTestCasesToFinish(): Generator
    {
        yield '' =>
        [
            new OperationTestCaseStubUpdatePet1(),
            [new ResponseMock(200, [])],
            [],
            OperationTestCase::STATUS_SUCCESS
        ];
    }

    /**
     * @test
     */
    public function finish_NotLaunch_ThrowException(): void
    {
        $this->expectException(InvalidStatusException::class);
        $operationTestCase = new OperationTestCaseStubUpdatePet1();
        $operationTestCase->finish([]);
    }

    /**
     * @test
     * @dataProvider getOperationTestCasesToLaunch
     * @param RequestInterface[] $expectedRequests
     */
    public function launch_ReturnRequests(OperationTestCase $operationTestCase, array $expectedRequests): void
    {
        $actualRequests = $operationTestCase->launch();
        $this->assertRequests($expectedRequests, $actualRequests);
    }

    /**
     * @test
     * @dataProvider getOperationTestCasesToFinish
     *
     * @param OperationTestCase   $operationTestCase
     * @param ResponseInterface[] $responses
     * @param string[][]          $expectedErrors
     *
     * @throws \OpenAPITesting\Models\Test\InvalidStatusException
     */
    public function finish_ReturnErrors(OperationTestCase $operationTestCase, array $responses, array $expectedErrors, $expectedStatus): void
    {
        $operationTestCase->launch();
        $operationTestCase->finish($responses);
        $this->assertEquals($expectedErrors, $operationTestCase->getErrors());
        $this->assertEquals($expectedStatus, $operationTestCase->getStatus());
    }
}
