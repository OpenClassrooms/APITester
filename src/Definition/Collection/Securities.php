<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\Security;

/**
 * @method Security[] getIterator()
 * @extends Collection<array-key, Security>
 */
final class Securities extends Collection
{
}
