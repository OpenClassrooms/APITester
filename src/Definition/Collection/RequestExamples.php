<?php

declare(strict_types=1);

namespace APITester\Definition\Collection;

use APITester\Definition\RequestExample;
use Illuminate\Support\Collection;

/**
 * @method RequestExample[] getIterator()
 * @extends Collection<array-key, RequestExample>
 */
final class RequestExamples extends Collection
{
    /**
     * @var RequestExample[]
     */
    protected $items;
}
