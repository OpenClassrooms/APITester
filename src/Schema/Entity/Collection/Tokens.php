<?php

declare(strict_types=1);

namespace APITester\Schema\Entity\Collection;

use APITester\Schema\Entity\Token;
use Illuminate\Support\Collection;

/**
 * @method Token[] getIterator()
 * @extends Collection<array-key, Token>
 */
final class Tokens extends Collection
{
}
