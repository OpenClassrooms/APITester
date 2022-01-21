<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Collection\Responses;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Array_;
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
    public function prepare(Api $api): array
    {
        $testCases = [];
        foreach ($api->getOperations() as $operation) {
            $disallowedTypes = $this->pickDisallowedTypes(
                $operation->getResponses(),
                self::INVALID_TEST_CASES_NUMBER
            );
            foreach ($disallowedTypes as $status => $types) {
                foreach ($types as $type) {
                    $testCases[] = new TestCase(
                        "{$type}_{$status}_{$operation->getId()}",
                        new Request(
                            $operation->getMethod(),
                            $operation->getPath(),
                            [
                                'Accept' => $type,
                            ]
                        ),
                        new Response(406)
                    );
                }
            }
        }

        return $testCases;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function pickDisallowedTypes(Responses $responses, int $count = 3): array
    {
        $disallowedTypes = [];
        $statusCodes = $responses->getStatusCodes();
        foreach ($statusCodes as $statusCode) {
            $acceptTypes = $responses->getMediaTypes($statusCode);
            $notSupportedTypes = array_diff(Mime::TYPES, $acceptTypes);
            if (count($notSupportedTypes) === 0) {
                continue;
            }
            $disallowedTypes[$statusCode] = Array_::pickRandomItems($notSupportedTypes, $count);
        }

        return $disallowedTypes;
    }
}
