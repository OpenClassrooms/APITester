<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\Tag;

/**
 * @method Tag[] getIterator()
 * @extends Collection<array-key, Tag>
 */
final class Tags extends Collection
{
}
