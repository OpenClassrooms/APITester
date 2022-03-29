<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Server;
use Illuminate\Support\Collection;

/**
 * @method Server[] getIterator()
 * @extends Collection<array-key, Server>
 */
final class Servers extends Collection
{
}
