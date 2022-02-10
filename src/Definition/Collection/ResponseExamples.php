<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\ResponseExample;

/**
 * @method ResponseExample[] getIterator()
 * @extends Collection<array-key, ResponseExample>
 */
final class ResponseExamples extends Collection
{
}
