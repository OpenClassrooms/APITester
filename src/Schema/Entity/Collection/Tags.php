<?php

declare(strict_types=1);

namespace APITester\Schema\Entity\Collection;

use APITester\Schema\Entity\Tag;
use Illuminate\Support\Collection;

/**
 * @method Tag[] getIterator()
 * @extends Collection<array-key, Tag>
 */
final class Tags extends Collection
{
}
