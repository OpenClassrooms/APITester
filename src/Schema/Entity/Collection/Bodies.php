<?php

declare(strict_types=1);

namespace APITester\Schema\Entity\Collection;

use APITester\Schema\Entity\Body;
use Illuminate\Support\Collection;

/**
 * @method Body[] getIterator()
 * @method Bodies map(callable $c)
 * @extends Collection<array-key, Body>
 */
final class Bodies extends Collection
{
}
