<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use cebe\openapi\spec\OpenApi;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Test\TestCase;

final class Error405TestCasesPreparator extends TestCasesPreparator
{
    public const SUPPORTED_HTTP_METHODS = [
        'get',
        'post',
        'put',
        'patch',
        'delete',
        'head',
        'options',
        'trace',
        'connect',
    ];

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
            $disallowedMethods = array_diff(self::SUPPORTED_HTTP_METHODS, array_keys($pathInfo->getOperations()));
            foreach ($disallowedMethods as $disallowedMethod) {
                $testCases[] = new TestCase(
                    "{$disallowedMethod}_{$path}",
                    new Request(
                        $disallowedMethod,
                        $path
                    ),
                    new Response(405)
                );
            }
        }

        return $testCases;
    }
}
