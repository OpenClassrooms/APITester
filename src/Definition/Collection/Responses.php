<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\Response;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method Response[] getIterator()
 * @method Response[] toArray()
 */
final class Responses extends ArrayCollection
{
    /**
     * @return int[]
     */
    public function getStatusCodes(): array
    {
        $statusCodes = [];
        foreach ($this->toArray() as $element) {
            $statusCodes[] = $element->getStatusCode();
        }

        return $statusCodes;
    }

    /**
     * @return string[]
     */
    public function getMediaTypes(int $statusCode): array
    {
        $mediaTypes = [];
        foreach ($this->toArray() as $element) {
            if ($element->getStatusCode() !== $statusCode) {
                continue;
            }
            $mediaTypes[] = $element->getMediaType();
        }

        return $mediaTypes;
    }
}
