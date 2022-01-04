<?php

declare(strict_types=1);

namespace OpenAPITesting\Test\Preparator;

use cebe\openapi\spec\OpenApi;

interface TestCasesPreparator
{
    /**
     * @throws PreparatorLoadingException
     *
     * @return array<\OpenAPITesting\Test\TestCase>
     */
    public function __invoke(OpenApi $openApi): array;

    public static function getName(): string;

    /**
     * @param array<string, mixed> $config
     *
     * @throws \OpenAPITesting\Test\Preparator\InvalidPreparatorConfigException
     */
    public function configure(array $config): void;
}
