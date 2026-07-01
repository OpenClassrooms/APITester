<?php

declare(strict_types=1);

namespace APITester\Schema\Entity\Collection;

use APITester\Schema\Entity\Server;
use Illuminate\Support\Collection;

/**
 * @method Server[] getIterator()
 * @extends Collection<array-key, Server>
 */
final class Servers extends Collection
{
}
