<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\Header;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method Header[] getIterator()
 */
final class Headers extends ArrayCollection
{
}
