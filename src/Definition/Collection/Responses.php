<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Response;
use Illuminate\Support\Collection;

/**
 * @method Response[] getIterator()
 * @extends Collection<array-key, Response>
 */
final class Responses extends Collection
{
    use Comparable;
}
