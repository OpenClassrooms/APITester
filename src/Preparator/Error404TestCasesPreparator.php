<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Definition\Operation;
use OpenAPITesting\Definition\Response as DefinitionResponse;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

final class Error404TestCasesPreparator extends TestCasesPreparator
{
    public static function getName(): string
    {
        return '404';
    }

    /**
     * @inheritDoc
     */
    public function prepare(Api $api): iterable
    {
        /** @var Responses $responses */
        $responses = $api->getOperations()
            ->select('responses.*')
            ->flatten()
            ->where('statusCode', 404)
            ->values();

        return $responses
            ->map(fn (DefinitionResponse $response) => $this->prepareTestCase($response))
        ;
    }

    private function prepareTestCase(DefinitionResponse $response): TestCase
    {
        $nbParams = $response->getOperation()->getPathParameters()->count();
        $params = array_fill(0, $nbParams, -9999);

        return new TestCase(
            $response->getOperation()->getId(),
            new Request(
                $response->getOperation()->getMethod(),
                $response->getOperation()->getPath($params),
                [],
                $this->generateBody($response->getOperation()),
            ),
            new Response(
                404,
                [],
                $response->getDescription()
            ),
            $this->getGroups($response->getOperation()),
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
