<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\ResponseExample;
use Illuminate\Support\Collection;

/**
 * @method ResponseExample[] getIterator()
 * @extends Collection<array-key, ResponseExample>
 */
final class ResponseExamples extends Collection
{
    /**
     * @var ResponseExample[]
     */
    protected $items;
}
