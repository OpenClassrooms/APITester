<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAPITesting\Definition\SecurityScheme;

/**
 * @psalm-suppress ImplementedReturnTypeMismatch
 * @method SecurityScheme[] getIterator()
 */
final class SecuritySchemes extends ArrayCollection
{
}
