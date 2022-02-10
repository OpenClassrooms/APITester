<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\Request;

/**
 * @method Request[] getIterator()
 * @extends Collection<array-key, Request>
 */
final class Requests extends Collection
{
}
