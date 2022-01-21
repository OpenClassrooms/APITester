<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\Tag;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method Tag[] getIterator()
 */
final class Tags extends ArrayCollection
{
    /**
     * @return array<string>
     */
    public function toArray(): array
    {
        $array = [];
        try {
            foreach ($this->getIterator() as $tag) {
                $array[] = $tag->getName();
            }
        } catch (\Exception $e) {
            // @ignoreException
        }

        return $array;
    }
}
