<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Collection\Operations;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

final class Error404TestCasesPreparator extends TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    protected function generateTestCases(Operations $operations): iterable
    {
        /** @var Responses $responses */
        $responses = $operations
            ->select('responses.*')
            ->flatten()
            ->where('statusCode', 404)
            ->values()
        ;

        return $responses
            ->map(fn (DefinitionResponse $response) => $this->prepareTestCase($response))
        ;
    }

    private function prepareTestCase(DefinitionResponse $response): TestCase
    {
        $operation = $response->getParent();

        $request = new Request(
            $operation->getMethod(),
            $operation->getPath(
                array_fill(
                    0,
                    $response->getParent()
                        ->getPathParameters()
                        ->count(),
                    -9999
                )
            ),
            [],
            $this->generateBody($operation),
        );

        $request = $this->authenticate(
            $request,
            $operation,
        );

        return new TestCase(
            $operation->getId() . '_404',
            $request,
            new Response(
                404,
                [],
                $response->getDescription()
            ),
        );
    }

    private function generateBody(Operation $operation): ?string
    {
        $request = $operation->getRequest('application/json');

        if (null === $request) {
            return null;
        }

        return Json::encode(
            (array) (new SchemaFaker(
                $request->getBody(),
                new Options(),
                true
            ))->generate()
        );
    }
}
