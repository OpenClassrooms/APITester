<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Response;
use Illuminate\Support\Collection;

/**
 * @method Response[] getIterator()
 * @method Responses  map(callable $c)
 * @extends Collection<array-key, Response>
 */
final class Responses extends Collection
{
}
