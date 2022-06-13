<?php

declare(strict_types=1);

namespace APITester\Preparator;

final class Error413Preparator extends PaginationErrorPreparator
{
    public const TOO_LARGE_VALUES = [
        'name' => 'TooLargeRange',
        'lower' => '0',
        'upper' => '1000000000',
    ];

    protected function getStatusCode(): string
    {
        return '413';
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
