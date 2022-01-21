<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\Example;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method Example[] getIterator()
 */
final class Examples extends ArrayCollection
{
}
