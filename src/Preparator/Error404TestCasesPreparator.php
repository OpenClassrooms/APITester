<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
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
    public function prepare(Api $api): array
    {
        $testCases = [];

        foreach ($api->getOperations(['status' => 404]) as $operation) {
            $responses = $operation->getResponses(['status' => '404']);
            foreach ($responses as $response) {
                $testCases[] = new TestCase(
                    $operation->getId(),
                    new Request(
                        $operation->getMethod(),
                        $this->processPath($operation->getPath(), $operation),
                        [],
                        $this->generateBody($operation),
                    ),
                    new Response(
                        404,
                        [],
                        $response->getDescription()
                    ),
                    $this->getGroups($operation),
                );
            }
        }

        return array_filter($testCases);
    }

    private function processPath(string $path, Operation $operation): string
    {
        foreach ($operation->getParameters() as $parameter) {
            if ('path' === $parameter->getIn()) {
                $path = str_replace("{{$parameter->getName()}}", '-9999', $path);
            }
        }

        return $path;
    }

    private function generateBody(Operation $operation): ?string
    {
        $request = $operation->getRequest('application/json');
        if (null === $request->getBody()) {
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
