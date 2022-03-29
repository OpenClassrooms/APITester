<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Security;
use Illuminate\Support\Collection;

/**
 * @method Security[] getIterator()
 * @extends Collection<array-key, Security>
 */
final class Securities extends Collection
{
}
