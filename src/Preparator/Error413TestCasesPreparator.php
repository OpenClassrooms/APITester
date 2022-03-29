<?php

declare(strict_types=1);

namespace APITester\Preparator;

final class Error413TestCasesPreparator extends PaginationErrorTestCasesPreparator
{
    public const TOO_LARGE_VALUES = [
        'name' => 'too_large',
        'lower' => '0',
        'upper' => '1000000000',
    ];

    protected function getErrorCode(): int
    {
        return 413;
    }

    /**
     * @inheritDoc
     */
    protected function getHeaderValues(): array
    {
        return [self::TOO_LARGE_VALUES];
    }

    /**
     * @inheritDoc
     */
    protected function getQueryValues(): array
    {
        return [self::TOO_LARGE_VALUES];
    }
}
