<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use OpenAPITesting\Preparator\Exception\InvalidPreparatorConfigException;
use OpenAPITesting\Preparator\Exception\PreparatorLoadingException;
use OpenAPITesting\Test\TestCase;

abstract class TestCasesPreparator
{
    protected ?string $token = null;

    abstract public static function getName(): string;

    /**
     * @throws PreparatorLoadingException
     *
     * @return TestCase[]
     */
    abstract public function prepare(OpenApi $openApi): array;

    /**
     * @param array<array-key, mixed> $config
     *
     * @throws InvalidPreparatorConfigException
     */
    public function configure(array $config): void
    {
        if (isset($config['throw'])) {
            throw new InvalidPreparatorConfigException();
        }
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string[]
     */
    protected function getGroups(Operation $operation, string $method): array
    {
        return [
            $operation->operationId,
            $method,
            ...$operation->tags,
            'preparator_' . static::getName(),
        ];
    }
}
