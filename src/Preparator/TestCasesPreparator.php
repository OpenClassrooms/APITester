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

    /**
     * @throws PreparatorLoadingException
     *
     * @return iterable<TestCase>
     */
    abstract public function prepare(Api $api): iterable;

    /**
     * @param array<array-key, mixed> $rawConfig
     *
     * @throws InvalidPreparatorConfigException
     */
    public function configure(array $rawConfig): void
    {
        if (isset($rawConfig['throw'])) {
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
            ...$operation->getTags()
                ->select('name')
                ->toArray(),
            'preparator_' . static::getName(),
        ];
    }

    abstract public static function getName(): string;
}
