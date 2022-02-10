<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\Response;

/**
 * @method Response[] getIterator()
 * @extends Collection<array-key, Response>
 */
final class Responses extends Collection
{
}
