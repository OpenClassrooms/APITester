<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\Server;

/**
 * @method Server[] getIterator()
 * @extends Collection<array-key, Server>
 */
final class Servers extends Collection
{
}
