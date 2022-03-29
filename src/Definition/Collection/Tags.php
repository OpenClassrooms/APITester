<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Tag;
use Illuminate\Support\Collection;

/**
 * @method Tag[] getIterator()
 * @extends Collection<array-key, Tag>
 */
final class Tags extends Collection
{
}
