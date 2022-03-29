<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Request;
use Illuminate\Support\Collection;

/**
 * @method Request[] getIterator()
 * @method Requests  map(callable $c)
 * @extends Collection<array-key, Request>
 */
final class Requests extends Collection
{
    /**
     * @var Request[]
     */
    protected $items;
}
