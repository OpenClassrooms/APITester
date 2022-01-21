<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Response as OpenApiResponse;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Mime;

final class Error406TestCasesPreparator extends TestCasesPreparator
{
    private const INVALID_TEST_CASES_NUMBER = 3;

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
                    $disallowedTypes = $this->pickDisallowedTypes($response, self::INVALID_TEST_CASES_NUMBER);
                    foreach ($disallowedTypes as $type) {
                        $testCases[] = new TestCase(
                            "{$type}_{$statusCode}_{$method}_{$path}",
                            new Request(
                                mb_strtoupper($method),
                                $path,
                                [
                                    'Accept' => $type,
                                ]
                            ),
                            new Response(406)
                        );
                    }
                }
            }
        }

        return $testCases;
    }

    /**
     * @return string[]
     */
    private function pickDisallowedTypes(OpenApiResponse $response, int $num = 3): array
    {
        $acceptTypes = array_keys($response->content);

        $disallowedTypes = array_diff(Mime::TYPES, $acceptTypes);

        if ([] === $disallowedTypes) {
            return [];
        }

        /** @var int[] $randomTypesKeys */
        $randomTypesKeys = array_rand($disallowedTypes, $num);

        return array_filter(
            $disallowedTypes,
            static fn ($key) => \in_array($key, $randomTypesKeys, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
