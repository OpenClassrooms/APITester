<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\ExampleFixture;

/**
 * @method ExampleFixture[] getIterator()
 * @extends Collection<array-key, ExampleFixture>
 */
final class ExampleFixtures extends Collection
{
}
