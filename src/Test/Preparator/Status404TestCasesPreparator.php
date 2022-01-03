<?php

declare(strict_types=1);

namespace OpenAPITesting\Test\Preparator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Test\TestCase;

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
                if ('get' !== $method || ! isset($operation->responses['404'])) {
                    continue;
                }
                /** @var \cebe\openapi\spec\Response $response */
                $response = $operation->responses['404'];
                $testCases[] = new TestCase(
                    $operation->operationId,
                    new Request(
                        mb_strtoupper($method),
                        $this->processPath($path, $operation),
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

    public function getName(): string
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
}
