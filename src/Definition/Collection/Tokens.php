<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\Token;

/**
 * @method Token[] getIterator()
 * @extends Collection<array-key, Token>
 */
final class Tokens extends Collection
{
}
