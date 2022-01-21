<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

use OpenAPITesting\Definition\Api;
use OpenAPITesting\Definition\Operation;
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
    abstract public function prepare(Api $api): array;

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
    protected function getGroups(Operation $operation): array
    {
        return [
            $operation->getId(),
            $operation->getMethod(),
            ...$operation->getTags()->toArray(),
            'preparator_' . static::getName(),
        ];
    }
}
