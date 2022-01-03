<?php

declare(strict_types=1);

namespace OpenAPITesting\Test\Preparator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

final class Status404TestCasesPreparator implements TestCasesPreparator
{
    /**
     * @inheritDoc
     */
    public function __invoke(OpenApi $openApi): array
    {
        $testCases = [];
        /** @var string $path */
        foreach ($openApi->paths as $path => $pathInfo) {
            /** @var string $method */
            foreach ($pathInfo->getOperations() as $method => $operation) {
                if (!isset($operation->responses['404'])) {
                    continue;
                }
                /** @var \cebe\openapi\spec\Response $response */
                $response = $operation->responses['404'];
                $testCases[] = new TestCase(
                    $operation->operationId,
                    new Request(
                        mb_strtoupper($method),
                        $this->processPath($path, $operation),
                        [],
                        $this->generateBody($operation),
                    ),
                    new Response(
                        404,
                        [],
                        $response->description
                    ),
                    [$operation->operationId, $method, ...$operation->tags],
                );
            }
        }

        return $testCases;
    }

    public static function getName(): string
    {
        return '404';
    }

    private function processPath(string $path, Operation $operation): string
    {
        /** @var \cebe\openapi\spec\Parameter $parameter */
        foreach ($operation->parameters as $parameter) {
            if ('path' === $parameter->in) {
                $path = str_replace("{{$parameter->name}}", '-9999', $path);
            }
        }

        return $path;
    }

    private function generateBody(Operation $operation): ?string
    {
        if (!isset($operation->requestBody->content) || !isset($operation->requestBody->content['application/json'])) {
            return null;
        }
        /** @var \cebe\openapi\spec\Schema $schema */
        $schema = $operation->requestBody->content['application/json']->schema;

        return Json::encode((array) (new SchemaFaker($schema, new Options(), true))->generate());
    }
}
