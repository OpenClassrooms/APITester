<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\Response;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method Response[] getIterator()
 */
final class Responses extends ArrayCollection
{
}
