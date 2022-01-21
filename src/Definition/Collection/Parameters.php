<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\Parameter;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method Parameter[] getIterator()
 */
final class Parameters extends ArrayCollection
{
}
