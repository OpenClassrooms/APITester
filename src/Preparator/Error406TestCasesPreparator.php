<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use cebe\openapi\spec\OpenApi;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Mime;

final class Error406TestCasesPreparator extends TestCasesPreparator
{
    public static function getName(): string
    {
        return '405';
    }

    /**
     * @inheritDoc
     */
    public function prepare(OpenApi $openApi): array
    {
        $testCases = [];

        /** @var string $path */
        foreach ($openApi->paths as $path => $pathInfo) {
            /** @var string $method */
            foreach ($pathInfo->getOperations() as $method => $operation) {
                if (null === $operation->responses) {
                    continue;
                }
                foreach ($operation->responses as $statusCode => $response) {
                    $acceptTypes = array_keys($response->content);
                    $disallowedTypes = array_diff(Mime::TYPES, $acceptTypes);
                    $disallowedType = $disallowedTypes[array_rand($disallowedTypes)];
                    $testCases[] = new TestCase(
                        "{$disallowedType}_{$statusCode}_{$method}_{$path}",
                        new Request(
                            mb_strtoupper($method),
                            $path,
                            [
                                'Accept' => $disallowedType,
                            ]
                        ),
                        new Response(406)
                    );
                }
            }
        }

        return $testCases;
    }
}
