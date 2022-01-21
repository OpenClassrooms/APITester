<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\Operation;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method Operation[] getIterator()
 */
final class Operations extends ArrayCollection
{
}
