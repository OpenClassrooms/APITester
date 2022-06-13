<?php

declare(strict_types=1);

namespace APITester\Preparator;

final class Error416Preparator extends PaginationErrorPreparator
{
    public const NEGATIVE_VALUES = [
        'name' => 'NegativeRange',
        'lower' => '-5',
        'upper' => '5',
    ];
    public const NON_NUMERIC_VALUES = [
        'name' => 'NonNumericRange',
        'lower' => 'foo',
        'upper' => 'bar',
    ];
    public const INVERSED_VALUES = [
        'name' => 'InversedRange',
        'lower' => '20',
        'upper' => '5',
    ];

    protected function getStatusCode(): string
    {
        return '416';
    }

    /**
     * @inheritDoc
     */
    protected function getHeaderValues(): array
    {
        return [self::NON_NUMERIC_VALUES, self::INVERSED_VALUES];
    }

    /**
     * @inheritDoc
     */
    protected function getQueryValues(): array
    {
        return [self::NON_NUMERIC_VALUES, self::INVERSED_VALUES, self::NEGATIVE_VALUES];
    }
}
