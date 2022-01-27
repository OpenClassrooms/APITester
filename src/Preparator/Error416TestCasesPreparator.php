<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator;

class Error416TestCasesPreparator extends PaginationErrorTestCasesPreparator
{
    public const ERROR_CODE = 416;
    public const NEGATIVE_VALUES = [
        'name' => 'negative',
        'lower' => '-5',
        'upper' => '5',
    ];
    public const NON_NUMERIC_VALUES = [
        'name' => 'non_numeric',
        'lower' => 'foo',
        'upper' => 'bar',
    ];
    public const INVERSED_VALUES = [
        'name' => 'inversed',
        'lower' => '20',
        'upper' => '5',
    ];

    public static function getName(): string
    {
        return '416';
    }

    protected function getErrorCode(): int
    {
        return 416;
    }

    protected function getHeaderValues(): array
    {
        return [self::NON_NUMERIC_VALUES, self::INVERSED_VALUES];
    }

    protected function getQueryValues(): array
    {
        return [self::NON_NUMERIC_VALUES, self::INVERSED_VALUES, self::NEGATIVE_VALUES];
    }
}
