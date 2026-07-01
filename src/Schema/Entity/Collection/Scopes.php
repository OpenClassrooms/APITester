<?php

declare(strict_types=1);

namespace APITester\Schema\Entity\Collection;

use APITester\Schema\Entity\Scope;
use Illuminate\Support\Collection;

/**
 * @method Scope[] getIterator()
 * @extends Collection<array-key, Scope>
 */
final class Scopes extends Collection
{
    /**
     * @param string[] $names
     */
    public static function fromNames(array $names): self
    {
        return new self(array_map(static fn (string $n) => new Scope($n), $names));
    }
}
