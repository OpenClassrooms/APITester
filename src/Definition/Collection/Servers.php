<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\Server;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method Server[] getIterator()
 */
final class Servers extends ArrayCollection
{
}
