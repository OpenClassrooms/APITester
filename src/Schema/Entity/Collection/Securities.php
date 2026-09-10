<?php

declare(strict_types=1);

namespace APITester\Schema\Entity\Collection;

use APITester\Schema\Entity\Security;
use Illuminate\Support\Collection;

/**
 * @method Security[] getIterator()
 * @extends Collection<array-key, Security>
 */
final class Securities extends Collection
{
}
