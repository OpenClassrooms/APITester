<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

class Error413TestCasesPreparator extends PaginationErrorTestCasesPreparator
{
    public const TOO_LARGE_VALUES = [
        'name' => 'too_large',
        'lower' => '0',
        'upper' => '1000000000',
    ];

    public static function getName(): string
    {
        return '413';
    }

    protected function getErrorCode(): int
    {
        return 413;
    }

    protected function getHeaderValues(): array
    {
        return [self::TOO_LARGE_VALUES];
    }

    protected function getQueryValues(): array
    {
        return [self::TOO_LARGE_VALUES];
    }
}
