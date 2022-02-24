<?php

declare(strict_types=1);

namespace OpenAPITesting\Definition\Collection;

use Illuminate\Support\Collection;
use OpenAPITesting\Definition\ParameterExample;

/**
 * @method ParameterExample[] getIterator()
 * @extends Collection<array-key, ParameterExample>
 */
final class ParameterExamples extends Collection
{
    /**
     * @var ParameterExample[]
     */
    protected $items;
}
