<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\ParameterExample;
use Illuminate\Support\Collection;

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
