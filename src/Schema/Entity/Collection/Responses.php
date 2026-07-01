<?php

declare(strict_types=1);

namespace APITester\Schema\Entity\Collection;

use APITester\Schema\Entity\Response;
use Illuminate\Support\Collection;

/**
 * @method Response[] getIterator()
 * @method Responses  map(callable $c)
 * @extends Collection<array-key, Response>
 */
final class Responses extends Collection
{
}
