<?php

declare(strict_types=1);

namespace OpenAPITesting\Preparator\Config;

final class PaginationErrorConfig
{
    /**
     * @var PaginationErrorConfigItem[]
     */
    private array $items;

    /**
     * @return PaginationErrorConfigItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param PaginationErrorConfigItem[] $items
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function addItem(PaginationErrorConfigItem $item): self
    {
        $this->items[] = $item;

        return $this;
    }
}
