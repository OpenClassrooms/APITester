<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator\Config;

final class PaginationError
{
    /**
     * @var Range[]
     */
    private array $range;

    /**
     * @param Range[] $range
     */
    public function __construct(array $range)
    {
        $this->range = $range;
    }

    /**
     * @return Range[]
     */
    public function getRange(): array
    {
        return $this->range;
    }

    /**
     * @param Range[] $range
     */
    public function setRange(array $range): self
    {
        $this->range = $range;

        return $this;
    }

    public function addRangeItem(Range $item): self
    {
        $this->range[] = $item;

        return $this;
    }
}
