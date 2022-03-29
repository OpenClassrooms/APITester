<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\Token;
use Illuminate\Support\Collection;

/**
 * @method Token[] getIterator()
 * @extends Collection<array-key, Token>
 */
final class Tokens extends Collection
{
}
