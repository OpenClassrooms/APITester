<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use OpenAPITesting\Definition\Api;
use OpenAPITesting\Test\TestCase;
use OpenAPITesting\Util\Json;

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

    /**
     * @var array<array-key, mixed>
     */
    private array $responseBody = [];

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
        /** @var string $path */
        foreach ($api->paths as $path => $pathInfo) {
            $disallowedMethods = array_diff(self::SUPPORTED_HTTP_METHODS, array_keys($pathInfo->getOperations()));
            foreach ($disallowedMethods as $disallowedMethod) {
                $testCases[] = new TestCase(
                    "{$disallowedMethod}_{$path}",
                    new Request(
                        mb_strtoupper($disallowedMethod),
                        $path
                    ),
                    new Response(405, [], [] !== $this->responseBody ? Json::encode($this->responseBody) : null)
                );
            }
        }

        return $testCases;
    }

    /**
     * @inheritDoc
     */
    public function configure(array $config): void
    {
        parent::configure($config);

        if (isset($config['responseBody']) && \is_array($config['responseBody'])) {
            $this->responseBody = $config['responseBody'];
        }
    }
}
