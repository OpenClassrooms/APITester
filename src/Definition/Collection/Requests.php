<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\Request;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method Request[] getIterator()
 */
final class Requests extends ArrayCollection
{
}
